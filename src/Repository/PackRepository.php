<?php

namespace App\Repository;

use App\Entity\Pack;
use App\Entity\PackService;
use App\Entity\GService;
use App\Entity\Typepack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class PackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pack::class);
    }

    /**
     * Enrichit les packs avec Typepack et services associés.
     *
     * @param Pack[] $packs Tableau d'entités Pack à enrichir
     * @param EntityManagerInterface $entityManager EntityManager pour gérer les entités
     * @return Pack[] Tableau des packs enrichis
     */
    public function enrichPacks(array $packs, EntityManagerInterface $entityManager): array
    {
        $typepackRepository = $entityManager->getRepository(Typepack::class);
        $packServiceRepository = $entityManager->getRepository(PackService::class);
        $gServiceRepository = $entityManager->getRepository(GService::class);

        foreach ($packs as $pack) {
            // Gérer le Typepack
            $typepack = $pack->getTypepack();
            $typeValue = $pack->getType();

            if (!$typeValue) {
                $pack->setType('Anniversaire');
                $typeValue = 'Anniversaire';
                $entityManager->persist($pack);
            }

            if (!$typepack || !$typepack->getId()) {
                $typepack = $typepackRepository->findOneBy(['type' => $typeValue]);
                if (!$typepack) {
                    $typepack = new Typepack();
                    $typepack->setType($typeValue);
                    $entityManager->persist($typepack);
                }
                $pack->setTypepack($typepack);
                $entityManager->persist($pack);
            }

            // Récupérer les services associés
            $packServices = $packServiceRepository->findBy(['pack_id' => $pack->getId()]);
            $services = [];

            foreach ($packServices as $packService) {
                $service = $gServiceRepository->findOneBy(['titre' => $packService->getServiceTitre()]);
                if ($service) {
                    $services[] = $service;
                }
            }

            $pack->setServices($services);
        }

        $entityManager->flush();

        return $packs;
    }
}