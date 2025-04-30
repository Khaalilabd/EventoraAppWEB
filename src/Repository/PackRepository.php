<?php

namespace App\Repository;

use App\Entity\Favoris;
use App\Entity\Pack;
use App\Entity\PackService;
use App\Entity\GService;
use App\Entity\Typepack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pack>
 */
class PackRepository extends ServiceEntityRepository
{
    private $typepackRepository;
    private $packServiceRepository;
    private $gServiceRepository;

    public function __construct(
        ManagerRegistry $registry,
        \App\Repository\TypepackRepository $typepackRepository,
        \App\Repository\PackServiceRepository $packServiceRepository,
        \App\Repository\GServiceRepository $gServiceRepository
    ) {
        parent::__construct($registry, Pack::class);
        $this->typepackRepository = $typepackRepository;
        $this->packServiceRepository = $packServiceRepository;
        $this->gServiceRepository = $gServiceRepository;
    }

    /**
     * Populate typepack and services for a list of packs.
     *
     * @param Pack[] $packs
     * @return Pack[]
     */
    private function populateTypepackAndServices(array $packs): array
    {
        if (empty($packs)) {
            return $packs;
        }

        // Fetch all typepacks in one query
        $types = array_unique(array_map(fn($pack) => $pack->getType(), $packs));
        $typepacks = $this->typepackRepository->findBy(['type' => $types]);
        $typepackMap = [];
        foreach ($typepacks as $typepack) {
            $typepackMap[$typepack->getType()] = $typepack;
        }

        // Fetch all pack services for the given packs in one query
        $packIds = array_map(fn($pack) => $pack->getId(), $packs);
        $packServices = $this->packServiceRepository->findBy(['pack_id' => $packIds]);

        // Group pack services by pack_id
        $packServicesMap = [];
        foreach ($packServices as $packService) {
            $packServicesMap[$packService->getPack_id()][] = $packService->getService_titre();
        }

        // Fetch all GServices in one query
        $serviceTitres = array_unique(array_merge(...array_values($packServicesMap)));
        $gServices = $this->gServiceRepository->findBy(['titre' => $serviceTitres]);
        $gServiceMap = [];
        foreach ($gServices as $gService) {
            $gServiceMap[$gService->getTitre()] = $gService;
        }

        // Populate typepack and services for each pack
        foreach ($packs as $pack) {
            // Set typepack
            $packType = $pack->getType();
            if (isset($typepackMap[$packType])) {
                $pack->setTypepack($typepackMap[$packType]);
            }

            // Set services
            $packId = $pack->getId();
            $services = [];
            if (isset($packServicesMap[$packId])) {
                foreach ($packServicesMap[$packId] as $serviceTitre) {
                    if (isset($gServiceMap[$serviceTitre])) {
                        $services[] = $gServiceMap[$serviceTitre];
                    }
                }
            }
            $pack->setServices($services);
        }

        return $packs;
    }

    public function findAllPaginated(int $page, int $limit, string $sort, string $order, ?string $type, ?string $location, ?float $minPrice, ?float $maxPrice): array
{
    $qb = $this->createQueryBuilder('p');

    // Apply filters
    if ($type) {
        $qb->andWhere('p.type = :type')
           ->setParameter('type', $type);
    }
    if ($location) {
        $qb->andWhere('p.location = :location')
           ->setParameter('location', $location);
    }
    if ($minPrice !== null) {
        $qb->andWhere('p.prix >= :minPrice')
           ->setParameter('minPrice', $minPrice);
    }
    if ($maxPrice !== null) {
        $qb->andWhere('p.prix <= :maxPrice')
           ->setParameter('maxPrice', $maxPrice);
    }

    // Apply sorting
    $qb->orderBy("p.$sort", $order);

    // Pagination
    $offset = ($page - 1) * $limit;
    $qb->setFirstResult($offset)
       ->setMaxResults($limit);

    $packs = $qb->getQuery()->getResult();

    // Populate typepack and services
    $packs = $this->populateTypepackAndServices($packs);

    // Calculate total for pagination
    $totalQb = $this->createQueryBuilder('p')
                    ->select('COUNT(p.id)');
    if ($type) {
        $totalQb->andWhere('p.type = :type')
                ->setParameter('type', $type);
    }
    if ($location) {
        $totalQb->andWhere('p.location = :location')
                ->setParameter('location', $location);
    }
    if ($minPrice !== null) {
        $totalQb->andWhere('p.prix >= :minPrice')
                ->setParameter('minPrice', $minPrice);
    }
    if ($maxPrice !== null) {
        $totalQb->andWhere('p.prix <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
    }
    $total = $totalQb->getQuery()->getSingleScalarResult();

    return [
        'packs' => $packs,
        'total' => $total,
    ];
}
    /**
     * Search packs by nomPack or description with pagination and filters.
     *
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param string $type
     * @param string $location
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return array
     */
    public function search(
        string $query,
        int $page,
        int $limit,
        string $type = '',
        string $location = '',
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->where('LOWER(p.nomPack) LIKE :query')
            ->orWhere('LOWER(p.description) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%');

        // Apply filters
        if ($type !== '') {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($location !== '') {
            $qb->andWhere('p.location = :location')
               ->setParameter('location', $location);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.prix >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.prix <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Apply pagination (no sorting for search)
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $packs = $qb->getQuery()->getResult();

        // Populate typepack and services
        $packs = $this->populateTypepackAndServices($packs);

        // Count total search results
        $qbCount = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('LOWER(p.nomPack) LIKE :query')
            ->orWhere('LOWER(p.description) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%');

        if ($type !== '') {
            $qbCount->andWhere('p.type = :type')
                    ->setParameter('type', $type);
        }

        if ($location !== '') {
            $qbCount->andWhere('p.location = :location')
                    ->setParameter('location', $location);
        }

        if ($minPrice !== null) {
            $qbCount->andWhere('p.prix >= :minPrice')
                    ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qbCount->andWhere('p.prix <= :maxPrice')
                    ->setParameter('maxPrice', $maxPrice);
        }

        $total = (int) $qbCount->getQuery()->getSingleScalarResult();

        return [
            'packs' => $packs,
            'total' => $total,
        ];
    }

    /**
     * Find packs with pagination and filtering (for user-facing pages).
     *
     * @param int $page
     * @param int $limit
     * @param string $sort
     * @param string $order
     * @param string $type
     * @param string $location
     * @param float|null $minPrice
     * @param float|null $maxPrice
     * @return array
     */
    public function findFilteredForAll(
        int $page,
        int $limit,
        string $sort,
        string $order,
        string $type = '',
        string $location = '',
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->select('p');

        // Apply filters
        if ($type !== '') {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($location !== '') {
            $qb->andWhere('p.location = :location')
               ->setParameter('location', $location);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.prix >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.prix <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        // Apply sorting
        $qb->orderBy("p.$sort", $order);

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $packs = $qb->getQuery()->getResult();

        // Populate typepack and services
        $packs = $this->populateTypepackAndServices($packs);

        // Count total packs
        $qbCount = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        if ($type !== '') {
            $qbCount->andWhere('p.type = :type')
                    ->setParameter('type', $type);
        }

        if ($location !== '') {
            $qbCount->andWhere('p.location = :location')
                    ->setParameter('location', $location);
        }

        if ($minPrice !== null) {
            $qbCount->andWhere('p.prix >= :minPrice')
                    ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qbCount->andWhere('p.prix <= :maxPrice')
                    ->setParameter('maxPrice', $maxPrice);
        }

        $total = (int) $qbCount->getQuery()->getSingleScalarResult();

        return [
            'packs' => $packs,
            'total' => $total,
        ];
    }

    /**
     * Find similar packs based on favorites.
     *
     * @param Favoris[] $favorites
     * @param int $limit
     * @return Pack[]
     */
    public function findSimilarPacks(array $favorites, int $limit): array
    {
        if (empty($favorites)) {
            $packs = $this->createQueryBuilder('p')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
            return $this->populateTypepackAndServices($packs);
        }

        $packIds = array_map(fn($favorite) => $favorite->getPack()->getId(), $favorites);
        $types = array_unique(array_map(fn($favorite) => $favorite->getPack()->getType(), $favorites));
        $locations = array_unique(array_map(fn($favorite) => $favorite->getPack()->getLocation(), $favorites));

        $qb = $this->createQueryBuilder('p')
            ->where('p.id NOT IN (:packIds)')
            ->setParameter('packIds', $packIds);

        if (!empty($types)) {
            $qb->andWhere('p.type IN (:types)')
               ->setParameter('types', $types);
        }

        if (!empty($locations)) {
            $qb->andWhere('p.location IN (:locations)')
               ->setParameter('locations', $locations);
        }

        $packs = $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->populateTypepackAndServices($packs);
    }
    public function countByType(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.type as typepack, COUNT(p.id) as count')
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();
    }
}