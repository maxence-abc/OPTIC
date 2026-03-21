<?php

namespace App\Controller;

use App\Entity\AccountSuspension;
use App\Entity\User;
use App\Form\User1Type;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/moderation/users')]
final class AdminUserController extends AbstractController
{
    #[Route(name: 'app_admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'status' => (string) $request->query->get('status', 'all'),
            'role' => (string) $request->query->get('role', 'all'),
        ];

        if (!in_array($filters['status'], ['all', 'active', 'inactive'], true)) {
            $filters['status'] = 'all';
        }

        if (!in_array($filters['role'], ['all', 'client', 'pro', 'admin_pro', 'admin'], true)) {
            $filters['role'] = 'all';
        }

        $allUsers = $userRepository->findBy([], ['createdAt' => 'DESC']);
        $filteredUsers = array_values(array_filter(
            $allUsers,
            fn (User $user): bool => $this->matchesFilters($user, $filters)
        ));

        return $this->render('admin_user/index.html.twig', [
            'filters' => $filters,
            'stats' => $this->buildStats($allUsers),
            'users' => array_map(fn (User $user): array => $this->buildUserCard($user), $filteredUsers),
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin_user/show.html.twig', [
            'account' => $this->buildUserCard($user),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $adminUser = $this->getUser();

        if ($adminUser instanceof User && $adminUser->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte admin.');

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte a été supprimé.');
        }

        return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle', name: 'app_admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $redirectQuery = array_filter([
            'q' => trim((string) $request->request->get('q', '')),
            'status' => (string) $request->request->get('status', ''),
            'role' => (string) $request->request->get('role', ''),
        ], static fn (string $value): bool => $value !== '');

        if ($this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            $adminUser = $this->getUser();

            if (!$adminUser instanceof User) {
                $this->addFlash('error', 'Session admin invalide.');

                return $this->redirectToRoute('app_admin_user_index', $redirectQuery);
            }

            if ($adminUser->getId() === $user->getId()) {
                $this->addFlash('warning', 'Vous ne pouvez pas désactiver votre propre compte admin.');

                return $this->redirectToRoute('app_admin_user_index', $redirectQuery);
            }

            $isCurrentlyActive = $user->isActive() !== false;

            if ($isCurrentlyActive) {
                $user->setIsActive(false);

                $suspension = (new AccountSuspension())
                    ->setSuspendedUser($user)
                    ->setAdminUser($adminUser)
                    ->setReason('Compte désactivé depuis l’administration plateforme.')
                    ->setCreatedAt(new \DateTimeImmutable());

                $em->persist($suspension);
                $this->addFlash('success', sprintf('Le compte %s a été désactivé.', $user->getEmail()));
            } else {
                $user->setIsActive(true);

                foreach ($this->getSortedSuspensions($user) as $suspension) {
                    if ($suspension->getLiftedAt() === null) {
                        $suspension->setLiftedAt(new \DateTimeImmutable());
                        break;
                    }
                }

                $this->addFlash('success', sprintf('Le compte %s a été réactivé.', $user->getEmail()));
            }

            $user->setUpdateAt(new \DateTime());
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_user_index', $redirectQuery);
    }

    /**
     * @param array{q: string, status: string, role: string} $filters
     */
    private function matchesFilters(User $user, array $filters): bool
    {
        if ($filters['status'] === 'active' && $user->isActive() === false) {
            return false;
        }

        if ($filters['status'] === 'inactive' && $user->isActive() !== false) {
            return false;
        }

        if ($filters['role'] !== 'all' && $this->resolveRoleKey($user) !== $filters['role']) {
            return false;
        }

        if ($filters['q'] === '') {
            return true;
        }

        $needle = mb_strtolower($filters['q']);
        $haystack = mb_strtolower(implode(' ', array_filter([
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getSpecialization(),
            $user->getEstablishment()?->getName(),
            implode(' ', array_map(
                static fn ($establishment): string => $establishment->getName() ?? '',
                $user->getEstablishments()->toArray()
            )),
        ])));

        return str_contains($haystack, $needle);
    }

    /**
     * @param User[] $users
     *
     * @return array{total: int, active: int, inactive: int, admins: int}
     */
    private function buildStats(array $users): array
    {
        $active = count(array_filter($users, static fn (User $user): bool => $user->isActive() !== false));
        $admins = count(array_filter($users, fn (User $user): bool => $this->resolveRoleKey($user) === 'admin'));

        return [
            'total' => count($users),
            'active' => $active,
            'inactive' => count($users) - $active,
            'admins' => $admins,
        ];
    }

    /**
     * @return array{
     *     user: User,
     *     id: int|null,
     *     fullName: string,
     *     email: string,
     *     phone: string|null,
     *     roleKey: string,
     *     roleLabel: string,
     *     isActive: bool,
     *     assignedEstablishment: string|null,
     *     ownedEstablishments: string[],
     *     createdAt: \DateTimeImmutable|null,
     *     updatedAt: \DateTime|null,
     *     latestSuspension: array{reason: string, createdAt: \DateTimeImmutable|null, liftedAt: \DateTimeImmutable|null}|null
     * }
     */
    private function buildUserCard(User $user): array
    {
        $ownedEstablishments = array_values(array_filter(array_map(
            static fn ($establishment): string => $establishment->getName() ?? '',
            $user->getEstablishments()->toArray()
        )));

        $latestSuspension = null;
        $sortedSuspensions = $this->getSortedSuspensions($user);
        if ($sortedSuspensions !== []) {
            $latest = $sortedSuspensions[0];
            $latestSuspension = [
                'reason' => $latest->getReason() ?? '',
                'createdAt' => $latest->getCreatedAt(),
                'liftedAt' => $latest->getLiftedAt(),
            ];
        }

        return [
            'user' => $user,
            'id' => $user->getId(),
            'fullName' => $this->formatUserName($user),
            'email' => $user->getEmail() ?? '',
            'phone' => $user->getPhone(),
            'roleKey' => $this->resolveRoleKey($user),
            'roleLabel' => $this->resolveRoleLabel($user),
            'isActive' => $user->isActive() !== false,
            'assignedEstablishment' => $user->getEstablishment()?->getName(),
            'ownedEstablishments' => $ownedEstablishments,
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdateAt(),
            'latestSuspension' => $latestSuspension,
        ];
    }

    /**
     * @return AccountSuspension[]
     */
    private function getSortedSuspensions(User $user): array
    {
        $suspensions = $user->getAccountSuspensions()->toArray();
        usort(
            $suspensions,
            static fn (AccountSuspension $left, AccountSuspension $right): int =>
                ($right->getCreatedAt()?->getTimestamp() ?? 0) <=> ($left->getCreatedAt()?->getTimestamp() ?? 0)
        );

        return $suspensions;
    }

    private function formatUserName(User $user): string
    {
        $fullName = trim(implode(' ', array_filter([
            $user->getFirstName(),
            $user->getLastName(),
        ])));

        return $fullName !== '' ? $fullName : 'Compte sans nom';
    }

    private function resolveRoleKey(User $user): string
    {
        $roles = $user->getRoles();

        return match (true) {
            in_array('ROLE_ADMIN', $roles, true) => 'admin',
            in_array('ROLE_ADMIN_PRO', $roles, true) => 'admin_pro',
            in_array('ROLE_PRO', $roles, true) => 'pro',
            default => 'client',
        };
    }

    private function resolveRoleLabel(User $user): string
    {
        return match ($this->resolveRoleKey($user)) {
            'admin' => 'Admin plateforme',
            'admin_pro' => 'Admin établissement',
            'pro' => 'Professionnel',
            default => 'Client',
        };
    }
}
