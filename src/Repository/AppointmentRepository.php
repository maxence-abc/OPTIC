<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * Vérifie s'il existe un rendez-vous qui chevauche un intervalle donné
     * pour un professionnel donné, à une date donnée.
     *
     * Règles :
     * - chevauchement strict (start < end && end > start)
     * - statuts bloquants uniquement (pending, confirmed)
     */
    public function hasOverlapForProfessional(
        int $professionalId,
        \DateTime $date,
        \DateTime $start,
        \DateTime $end
    ): bool {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('IDENTITY(a.professional) = :proId')
            ->andWhere('a.date = :date')
            ->andWhere('a.status IN (:blocking)')
            ->andWhere('a.startTime < :end')
            ->andWhere('a.endTime > :start')
            ->setParameter('proId', $professionalId)
            ->setParameter('date', $date)
            ->setParameter('blocking', ['pending', 'confirmed'])
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Vérifie s'il existe un rendez-vous qui chevauche un intervalle donné
     * pour un équipement donné, à une date donnée.
     */
    public function hasOverlapForEquipment(
        int $equipementId,
        \DateTime $date,
        \DateTime $start,
        \DateTime $end
    ): bool {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('IDENTITY(a.equipement) = :eid')
            ->andWhere('a.date = :date')
            ->andWhere('a.status IN (:blocking)')
            ->andWhere('a.startTime < :end')
            ->andWhere('a.endTime > :start')
            ->setParameter('eid', $equipementId)
            ->setParameter('date', $date)
            ->setParameter('blocking', ['pending', 'confirmed'])
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Retourne les IDs des professionnels rattachés à un établissement.
     *
     * Règles :
     * - user.establishment_id = :eid
     * - roles contient ROLE_PRO (colonne JSON stockée par Symfony)
     */
    public function findProfessionalIdsForEstablishment(int $establishmentId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT id
            FROM "user"
            WHERE establishment_id = :eid
            AND EXISTS (
                SELECT 1
                FROM jsonb_array_elements_text(roles::jsonb) AS r(val)
                WHERE r.val = :role
            )
        ';

        $rows = $conn->fetchAllAssociative($sql, [
            'eid' => $establishmentId,
            'role' => 'ROLE_PRO',
        ]);

        return array_map(static fn(array $row) => (int) $row['id'], $rows);
    }

    /**
     * Réservations à venir d'un client (>= maintenant), hors annulées.
     * Tri ascendant.
     */
    public function findUpcomingForClient(User $client, int $limit = 50): array
    {
        $now = new \DateTimeImmutable();
        $today = new \DateTimeImmutable($now->format('Y-m-d'));
        $nowTime = new \DateTimeImmutable($now->format('H:i:s'));

        return $this->createQueryBuilder('a')
            ->andWhere('a.client = :client')
            ->andWhere('a.status != :cancelled')
            ->andWhere('(a.date > :today) OR (a.date = :today AND a.startTime >= :nowTime)')
            ->setParameter('client', $client)
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('today', $today)
            ->setParameter('nowTime', $nowTime)
            ->addOrderBy('a.date', 'ASC')
            ->addOrderBy('a.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Réservations passées d'un client (< maintenant).
     * Tri descendant.
     */
    public function findPastForClient(User $client, int $limit = 20): array
    {
        $now = new \DateTimeImmutable();
        $today = new \DateTimeImmutable($now->format('Y-m-d'));
        $nowTime = new \DateTimeImmutable($now->format('H:i:s'));

        return $this->createQueryBuilder('a')
            ->andWhere('a.client = :client')
            ->andWhere('(a.date < :today) OR (a.date = :today AND a.startTime < :nowTime)')
            ->setParameter('client', $client)
            ->setParameter('today', $today)
            ->setParameter('nowTime', $nowTime)
            ->addOrderBy('a.date', 'DESC')
            ->addOrderBy('a.startTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
