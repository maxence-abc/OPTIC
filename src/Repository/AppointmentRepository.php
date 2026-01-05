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

}
