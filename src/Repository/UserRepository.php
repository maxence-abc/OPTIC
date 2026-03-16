<?php

namespace App\Repository;

use App\Entity\Establishment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[]
     */
    public function findProfessionalsByEstablishment(Establishment $establishment): array
    {
        $professionals = $this->findBookableCandidatesByEstablishment($establishment);

        usort(
            $professionals,
            static fn (User $left, User $right): int => [$left->getFirstName() ?? '', $left->getLastName() ?? '']
                <=> [$right->getFirstName() ?? '', $right->getLastName() ?? '']
        );

        return $professionals;
    }

    /**
     * @return User[]
     */
    public function findBookableCandidatesByEstablishment(Establishment $establishment): array
    {
        $users = $this->createQueryBuilder('u')
            ->andWhere('u.establishment = :establishment OR u = :owner')
            ->setParameter('establishment', $establishment)
            ->setParameter('owner', $establishment->getOwner())
            ->getQuery()
            ->getResult();

        $candidates = array_values(array_filter(
            $users,
            static fn (User $user): bool => self::isTransferableProfessional($user)
        ));

        usort($candidates, function (User $left, User $right) use ($establishment): int {
            $priorityComparison = self::getBookingPriority($left, $establishment) <=> self::getBookingPriority($right, $establishment);

            if ($priorityComparison !== 0) {
                return $priorityComparison;
            }

            return [$left->getFirstName() ?? '', $left->getLastName() ?? '', $left->getId() ?? 0]
                <=> [$right->getFirstName() ?? '', $right->getLastName() ?? '', $right->getId() ?? 0];
        });

        return $candidates;
    }

    private static function isTransferableProfessional(User $user): bool
    {
        if ($user->isActive() === false) {
            return false;
        }

        $roles = $user->getRoles();

        return in_array('ROLE_PRO', $roles, true)
            || in_array('ROLE_ADMIN_PRO', $roles, true)
            || in_array('ROLE_ADMIN', $roles, true);
    }

    private static function getBookingPriority(User $user, Establishment $establishment): int
    {
        $roles = $user->getRoles();
        $isOwner = $establishment->getOwner()?->getId() === $user->getId();

        if (in_array('ROLE_PRO', $roles, true) && !in_array('ROLE_ADMIN_PRO', $roles, true) && !in_array('ROLE_ADMIN', $roles, true)) {
            return 0;
        }

        if ((in_array('ROLE_ADMIN_PRO', $roles, true) || in_array('ROLE_ADMIN', $roles, true)) && !$isOwner) {
            return 1;
        }

        if ($isOwner) {
            return 2;
        }

        return 3;
    }
}
