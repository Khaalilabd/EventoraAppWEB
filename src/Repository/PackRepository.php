<?php

namespace App\Repository;

use App\Entity\Pack;
use App\Entity\Typepack;
use App\Entity\PackService;
use App\Entity\GService;
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

        $entityManager = $this->getEntityManager();
        $typepackRepository = $entityManager->getRepository(Typepack::class);
        $packServiceRepository = $entityManager->getRepository(PackService::class);
        $gServiceRepository = $entityManager->getRepository(GService::class);

        foreach ($packs as $pack) {
            // Populate typepack
            $typepack = $typepackRepository->findOneBy(['type' => $pack->getType()]);
            $pack->setTypepack($typepack);

            // Fetch associated services
            $packServices = $packServiceRepository->findBy(['pack_id' => $pack->getId()]);
            $services = [];
            foreach ($packServices as $packService) {
                $service = $gServiceRepository->findOneBy(['titre' => $packService->getServiceTitre()]);
                if ($service) {
                    $services[] = $service;
                }
            }
            // Store services in a transient property
            $pack->setServices($services);
        }

        return $packs;
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?Pack
    {
        $pack = parent::find($id, $lockMode, $lockVersion);
        if ($pack) {
            $entityManager = $this->getEntityManager();
            $typepackRepository = $entityManager->getRepository(Typepack::class);
            $packServiceRepository = $entityManager->getRepository(PackService::class);
            $gServiceRepository = $entityManager->getRepository(GService::class);

            // Populate typepack
            $typepack = $typepackRepository->findOneBy(['type' => $pack->getType()]);
            $pack->setTypepack($typepack);

            // Fetch associated services
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
        return $pack;
    }

    /**
     * Search packs by nomPack, description, prix, location, type, nbrGuests, or service_titre
     *
     * @param string $query
     * @return Pack[]
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if (empty($query)) {
            return $this->findAll();
        }

        $qb = $this->createQueryBuilder('p');
        $qb->leftJoin('App\Entity\PackService', 'ps', 'WITH', 'ps.pack_id = p.id')
           ->where(
               $qb->expr()->orX(
                   $qb->expr()->like('p.nomPack', ':query'),
                   $qb->expr()->like('p.description', ':query'),
                   $qb->expr()->like('CAST(p.prix AS string)', ':query'),
                   $qb->expr()->like('p.location', ':query'),
                   $qb->expr()->like('p.type', ':query'),
                   $qb->expr()->like('CAST(p.nbrGuests AS string)', ':query'),
                   $qb->expr()->like('ps.service_titre', ':query')
               )
           )
           ->setParameter('query', '%' . $query . '%')
           ->groupBy('p.id'); // Ensure unique packs

        $packs = $qb->getQuery()->getResult();

        // Populate typepack and services (as in findAll)
        $entityManager = $this->getEntityManager();
        $typepackRepository = $entityManager->getRepository(Typepack::class);
        $packServiceRepository = $entityManager->getRepository(PackService::class);
        $gServiceRepository = $entityManager->getRepository(GService::class);

        foreach ($packs as $pack) {
            // Populate typepack
            $typepack = $typepackRepository->findOneBy(['type' => $pack->getType()]);
            $pack->setTypepack($typepack);

            // Fetch associated services
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

        return $packs;
    }
    
    /**
     * Find all packs with pagination, populating typepack and services
     *
     * @param int $page
     * @param int $limit
     * @return Pack[]
     */
    public function findAllPaginated(int $page, int $limit): array
    {
        // Récupérer les packs avec pagination
        $queryBuilder = $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $packs = $queryBuilder->getQuery()->getResult();

        // Remplir typepack et services
        $entityManager = $this->getEntityManager();
        $typepackRepository = $entityManager->getRepository(Typepack::class);
        $packServiceRepository = $entityManager->getRepository(PackService::class);
        $gServiceRepository = $entityManager->getRepository(GService::class);

        foreach ($packs as $pack) {
            // Populate typepack
            $typepack = $typepackRepository->findOneBy(['type' => $pack->getType()]);
            $pack->setTypepack($typepack);

            // Fetch associated services
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

        return $packs;
    }
}