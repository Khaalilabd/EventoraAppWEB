<?php

namespace App\Repository;

use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * Trouve les feedbacks avec pagination, tri et filtres.
     *
     * @param int $page
     * @param int $limit
     * @param string $sortBy
     * @param string $sortOrder
     * @param string|null $userFilter
     * @param string|null $dateFilter
     * @return array
     */
    public function findPaginatedFeedbacks(int $page, int $limit, string $sortBy, string $sortOrder, ?string $userFilter, ?string $dateFilter): array
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->leftJoin('f.membre', 'm')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($userFilter) {
            $queryBuilder->andWhere('m.email = :email')
                         ->setParameter('email', $userFilter);
        }

        if ($dateFilter) {
            $queryBuilder->andWhere('DATE(f.date) = :selected_date')
                         ->setParameter('selected_date', $dateFilter);
        }

        switch ($sortBy) {
            case 'membre.email':
                $queryBuilder->orderBy('m.email', $sortOrder);
                break;
            case 'Vote':
                $queryBuilder->orderBy('f.Vote', $sortOrder);
                break;
            case 'date':
                $queryBuilder->orderBy('f.date', $sortOrder);
                break;
            case 'recommend':
                $queryBuilder->orderBy('f.recommend', $sortOrder);
                break;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Compte le nombre total de feedbacks avec filtres.
     *
     * @param string|null $userFilter
     * @param string|null $dateFilter
     * @return int
     */
    public function countFilteredFeedbacks(?string $userFilter, ?string $dateFilter): int
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->leftJoin('f.membre', 'm')
            ->select('COUNT(f.id)');

        if ($userFilter) {
            $queryBuilder->andWhere('m.email = :email')
                         ->setParameter('email', $userFilter);
        }

        if ($dateFilter) {
            $queryBuilder->andWhere('DATE(f.date) = :selected_date')
                         ->setParameter('selected_date', $dateFilter);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}