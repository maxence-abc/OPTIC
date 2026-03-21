<?php

namespace App\Repository;

use App\Entity\Establishment;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[]
     */
    public function findPublicByEstablishment(Establishment $establishment, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('client')
            ->leftJoin('r.client', 'client')
            ->andWhere('r.establishment = :establishment')
            ->setParameter('establishment', $establishment)
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Review[]
     */
    public function findByClient(User $client, int $limit = 50): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('appointment', 'service', 'establishment')
            ->leftJoin('r.appointment', 'appointment')
            ->leftJoin('appointment.service', 'service')
            ->leftJoin('r.establishment', 'establishment')
            ->andWhere('r.client = :client')
            ->setParameter('client', $client)
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Review[]
     */
    public function findByEstablishment(Establishment $establishment, int $limit = 50): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('client', 'appointment', 'service')
            ->leftJoin('r.client', 'client')
            ->leftJoin('r.appointment', 'appointment')
            ->leftJoin('appointment.service', 'service')
            ->andWhere('r.establishment = :establishment')
            ->setParameter('establishment', $establishment)
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{average: float, count: int}
     */
    public function getSummaryForEstablishment(Establishment $establishment): array
    {
        $row = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS averageRating, COUNT(r.id) AS reviewCount')
            ->andWhere('r.establishment = :establishment')
            ->setParameter('establishment', $establishment)
            ->getQuery()
            ->getSingleResult();

        return [
            'average' => isset($row['averageRating']) ? round((float) $row['averageRating'], 1) : 0.0,
            'count' => (int) ($row['reviewCount'] ?? 0),
        ];
    }
}
