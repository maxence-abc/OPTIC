<?php

namespace App\Repository;

use App\Entity\Establishment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Establishment>
 */
final class EstablishmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Establishment::class);
    }

    /**
     * Listing "marketplace" :
     * - récupère aussi images + services (cards)
     *
     * @return Establishment[]
     */
    public function findForListing(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.establishmentImages', 'ei')->addSelect('ei')
            ->leftJoin('e.services', 's')->addSelect('s')
            ->orderBy('e.id', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Listing "pro" : mes établissements
     *
     * @return Establishment[]
     */
    public function findForOwner(User $owner): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.owner = :owner')
            ->setParameter('owner', $owner)
            ->leftJoin('e.establishmentImages', 'ei')->addSelect('ei')
            ->leftJoin('e.services', 's')->addSelect('s')
            ->orderBy('e.id', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche "marketplace" avec filtres GET:
     * - q : nom/description établissement + nom/description service
     * - city : ville ou code postal
     * - category : e.category (valeur en base)
     *
     * @return Establishment[]
     */
    public function searchForListing(?string $q, ?string $city, ?string $category): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.establishmentImages', 'ei')->addSelect('ei')
            ->leftJoin('e.services', 's')->addSelect('s')
            ->orderBy('e.id', 'DESC')
            ->distinct();

        // --- Filtre q (service / établissement) ---
        if ($q !== null && $q !== '') {
            $qLike = '%' . mb_strtolower(trim($q)) . '%';

            $qb->andWhere('
                LOWER(e.name) LIKE :q
                OR LOWER(COALESCE(e.description, \'\')) LIKE :q
                OR LOWER(s.name) LIKE :q
                OR LOWER(COALESCE(s.description, \'\')) LIKE :q
            ')
            ->setParameter('q', $qLike);
        }

        // --- Filtre city (ville ou code postal) ---
        if ($city !== null && $city !== '') {
            $cityLike = '%' . mb_strtolower(trim($city)) . '%';

            $qb->andWhere('
                LOWER(e.city) LIKE :city
                OR LOWER(e.postalCode) LIKE :city
            ')
            ->setParameter('city', $cityLike);
        }

        // --- Filtre category (champ BDD) ---
        if ($category !== null && $category !== '' && $category !== 'all') {
            $qb->andWhere('e.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }
}
