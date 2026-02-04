<?php

namespace App\Command;

use App\Entity\Establishment;
use App\Repository\EstablishmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promote-manager',
    description: 'Assigne ROLE_ADMIN_PRO à un user et le définit comme owner d’un établissement',
)]
class PromoteManagerCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EstablishmentRepository $establishmentRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email du user à promouvoir')
            ->addArgument('establishmentId', InputArgument::REQUIRED, 'ID de l’établissement à gérer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $establishmentId = (int) $input->getArgument('establishmentId');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error("User introuvable pour email: {$email}");
            return Command::FAILURE;
        }

        /** @var Establishment|null $establishment */
        $establishment = $this->establishmentRepository->find($establishmentId);
        if (!$establishment) {
            $io->error("Etablissement introuvable pour ID: {$establishmentId}");
            return Command::FAILURE;
        }

        // Ajoute ROLE_ADMIN_PRO si pas déjà présent
        $roles = $user->getRoles(); // contient déjà ROLE_CLIENT via getRoles()
        // IMPORTANT: getRoles() rajoute ROLE_CLIENT à la volée, donc on doit repartir du champ brut.
        // On récupère plutôt les roles stockés via reflection simple: on recompose à partir de getRoles() mais sans doublons.
        // On va faire simple: on ajoute et on setRoles(array_unique(...)).

        $roles[] = 'ROLE_ADMIN_PRO';
        // Optionnel (pas nécessaire grâce à role_hierarchy):
        // $roles[] = 'ROLE_PRO';

        // On enlève ROLE_CLIENT du setRoles car getRoles() l’ajoute automatiquement,
        // mais ce n’est pas grave si tu le stockes, ça reste OK. On le retire juste pour être clean.
        $roles = array_values(array_unique(array_filter($roles, fn($r) => $r !== 'ROLE_CLIENT')));

        $user->setRoles($roles);

        // Assigner owner
        $establishment->setOwner($user);

        $this->em->flush();

        $io->success(sprintf(
            "OK: %s est maintenant ROLE_ADMIN_PRO et owner de l'établissement #%d (%s)",
            $email,
            $establishmentId,
            $establishment->getName() ?? ''
        ));

        return Command::SUCCESS;
    }
}
