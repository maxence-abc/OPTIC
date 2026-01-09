<?php

namespace App\Repository;

use App\Entity\Appointment;
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
     * Règles (alignées avec security.yaml) :
     * - user.establishment_id = :eid
     * - role = ROLE_PRO
     * - actif (is_active = true ou NULL)
     */
    public function findProfessionalIdsForEstablishment(int $establishmentId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT id
                FROM \"user\"
                WHERE establishment_id = :eid
                  AND role = 'ROLE_PRO'
                  AND (is_active = true OR is_active IS NULL)";

        $rows = $conn->fetchAllAssociative($sql, [
            'eid' => $establishmentId,
        ]);

        return array_map(static fn(array $row) => (int) $row['id'], $rows);
    }
}
