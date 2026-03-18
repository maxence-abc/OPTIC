<?php

namespace App\Repository;

use App\Entity\EmployeeWeeklySchedule;
use App\Entity\Establishment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeWeeklySchedule>
 */
class EmployeeWeeklyScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeWeeklySchedule::class);
    }

    /**
     * @return EmployeeWeeklySchedule[]
     */
    public function findByEmployee(Establishment $establishment, User $employee): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.establishment = :establishment')
            ->andWhere('s.employee = :employee')
            ->setParameter('establishment', $establishment)
            ->setParameter('employee', $employee)
            ->addOrderBy('s.dayOfWeek', 'ASC')
            ->addOrderBy('s.periodIndex', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User[] $employees
     * @return EmployeeWeeklySchedule[]
     */
    public function findByEmployees(array $employees): array
    {
        if ($employees === []) {
            return [];
        }

        return $this->createQueryBuilder('s')
            ->leftJoin('s.employee', 'employee')
            ->addSelect('employee')
            ->andWhere('s.employee IN (:employees)')
            ->setParameter('employees', $employees)
            ->addOrderBy('employee.first_name', 'ASC')
            ->addOrderBy('employee.lastName', 'ASC')
            ->addOrderBy('s.dayOfWeek', 'ASC')
            ->addOrderBy('s.periodIndex', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return EmployeeWeeklySchedule[]
     */
    public function findByEstablishment(Establishment $establishment): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.employee', 'employee')
            ->addSelect('employee')
            ->andWhere('s.establishment = :establishment')
            ->setParameter('establishment', $establishment)
            ->addOrderBy('employee.first_name', 'ASC')
            ->addOrderBy('employee.lastName', 'ASC')
            ->addOrderBy('s.dayOfWeek', 'ASC')
            ->addOrderBy('s.periodIndex', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
