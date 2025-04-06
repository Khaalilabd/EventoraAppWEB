<?php

namespace App\Repository;

use App\Entity\Reservationpack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationpackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservationpack::class);
    }

    public function findAllCustom(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->getQuery()
            ->getResult();
    }

    // Optionally, override find() for edit/delete consistency
    public function findCustom($id): ?Reservationpack
    {
        return $this->createQueryBuilder('r')
            ->where('r.IDReservationPack = :id') // Use the exact column name from the database
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}