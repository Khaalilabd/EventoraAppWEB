<?php

namespace App\Repository;

use App\Entity\Membre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membre>
 */
class MembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membre::class);
    }

    /**
     * Récupère tous les membres ayant un rôle spécifique.
     *
     * @param string $role Le rôle à filtrer (ex: "MEMBRE", "ADMIN", "AGENT")
     * @return Membre[] Liste des membres avec le rôle spécifié
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getResult();
    }
}