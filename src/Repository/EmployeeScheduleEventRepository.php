<?php

namespace App\Repository;

use App\Entity\EmployeeScheduleEvent;
use App\Entity\Establishment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeScheduleEvent>
 */
class EmployeeScheduleEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeScheduleEvent::class);
    }

    /**
     * @return EmployeeScheduleEvent[]
     */
    public function findByEmployeeBetweenDates(User $employee, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $start = \DateTimeImmutable::createFromInterface($from)->setTime(0, 0, 0);
        $end = \DateTimeImmutable::createFromInterface($to)->setTime(0, 0, 0);

        return $this->createQueryBuilder('e')
            ->andWhere('e.employee = :employee')
            ->andWhere('e.startDate <= :end')
            ->andWhere('e.endDate >= :start')
            ->setParameter('employee', $employee)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->addOrderBy('e.startDate', 'ASC')
            ->addOrderBy('e.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return EmployeeScheduleEvent[]
     */
    public function findByEstablishmentBetweenDates(
        Establishment $establishment,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        ?User $employee = null
    ): array {
        $start = \DateTimeImmutable::createFromInterface($from)->setTime(0, 0, 0);
        $end = \DateTimeImmutable::createFromInterface($to)->setTime(0, 0, 0);

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.employee', 'employee')
            ->addSelect('employee')
            ->andWhere('e.establishment = :establishment')
            ->andWhere('e.startDate <= :end')
            ->andWhere('e.endDate >= :start')
            ->setParameter('establishment', $establishment)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->addOrderBy('e.startDate', 'ASC')
            ->addOrderBy('e.startTime', 'ASC')
            ->addOrderBy('employee.first_name', 'ASC')
            ->addOrderBy('employee.lastName', 'ASC');

        if ($employee instanceof User) {
            $qb
                ->andWhere('e.employee = :employee')
                ->setParameter('employee', $employee);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return EmployeeScheduleEvent[]
     */
    public function findUpcomingByEstablishment(Establishment $establishment, int $limit = 25): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('e')
            ->leftJoin('e.employee', 'employee')
            ->addSelect('employee')
            ->andWhere('e.establishment = :establishment')
            ->andWhere('e.endDate >= :today')
            ->setParameter('establishment', $establishment)
            ->setParameter('today', $today)
            ->addOrderBy('e.startDate', 'ASC')
            ->addOrderBy('e.startTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
