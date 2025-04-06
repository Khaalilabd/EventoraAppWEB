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

    /**
     * Récupère une réservation pack par ID sans charger Pack.
     * @param mixed $id
     * @return Reservationpack|null
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?Reservationpack
    {
        return $this->createQueryBuilder('r')
            ->select('r') // Sélectionne uniquement les champs de Reservationpack
            ->where('r.IDReservationPack = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère toutes les réservations pack sans charger Pack.
     * @return Reservationpack[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r') // Sélectionne uniquement les champs de Reservationpack
            ->getQuery()
            ->getResult();
    }
}