<?php

namespace App\Repository;

use App\Entity\Reservationpack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservationpack>
 */
class ReservationpackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservationpack::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.IDReservationPack)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.IDReservationPack)')
            ->where('r.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d'))
            ->setParameter('end', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.IDReservationPack)')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageReservationValue(\DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        return (float) $this->createQueryBuilder('r')
            ->select('AVG(p.prix) as avgPrice')
            ->join('r.pack', 'p')
            ->where('r.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d'))
            ->setParameter('end', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}   