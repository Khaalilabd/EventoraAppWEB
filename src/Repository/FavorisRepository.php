<?php

namespace App\Repository;

use App\Entity\Favoris;
use App\Entity\Membre;
use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favoris>
 *
 * @method Favoris|null find($id, $lockMode = null, $lockVersion = null)
 * @method Favoris|null findOneBy(array $criteria, array $orderBy = null)
 * @method Favoris[]    findAll()
 * @method Favoris[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FavorisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favoris::class);
    }

    /**
     * Find a favorite entry by pack and member.
     */
    public function findOneByPackAndMembre(Pack $pack, Membre $membre): ?Favoris
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.pack = :pack')
            ->andWhere('f.membre = :membre')
            ->setParameter('pack', $pack)
            ->setParameter('membre', $membre)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all favorite packs for a member.
     *
     * @return Favoris[]
     */
    public function findByMembre(Membre $membre): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.membre = :membre')
            ->setParameter('membre', $membre)
            ->getQuery()
            ->getResult();
    }
}