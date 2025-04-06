<?php

namespace App\Repository;

use App\Entity\Pack;
use App\Entity\Typepack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pack>
 */
class PackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pack::class);
    }

    public function findAll(): array
    {
        $packs = parent::findAll(); // Fetch all Pack entities

        // Populate the typepack property for each Pack
        foreach ($packs as $pack) {
            $typepack = $this->getEntityManager()
                ->getRepository(Typepack::class)
                ->findOneBy(['type' => $pack->getType()]);
            $pack->setTypepack($typepack);
        }

        return $packs;
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?Pack
    {
        $pack = parent::find($id, $lockMode, $lockVersion);
        if ($pack) {
            $typepack = $this->getEntityManager()
                ->getRepository(Typepack::class)
                ->findOneBy(['type' => $pack->getType()]);
            $pack->setTypepack($typepack);
        }
        return $pack;
    }
}