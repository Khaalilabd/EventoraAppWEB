<?php

namespace App\Repository;

use App\Entity\Reservationpersonnalise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservationpersonnalise>
 */
class ReservationpersonnaliseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservationpersonnalise::class);
    }

    public function findByMembre($membreId)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.membre = :membre')
            ->setParameter('membre', $membreId)
            ->getQuery()
            ->getResult();
    }
    public function countByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
{
    return (int) $this->createQueryBuilder('r')
        ->select('COUNT(r.IDReservationPersonalise)')
        ->where('r.date BETWEEN :start AND :end')
        ->setParameter('start', $startDate)
        ->setParameter('end', $endDate)
        ->getQuery()
        ->getSingleScalarResult();
}
}