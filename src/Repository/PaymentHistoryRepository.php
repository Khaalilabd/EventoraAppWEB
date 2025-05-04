<?php

namespace App\Repository;

use App\Entity\PaymentHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentHistory>
 */
class PaymentHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentHistory::class);
    }

    /**
     * Trouve les paiements effectués entre deux dates
     * 
     * @param \DateTime|\DateTimeImmutable $startDate Date de début
     * @param \DateTime|\DateTimeImmutable $endDate Date de fin
     * @return PaymentHistory[] Liste des paiements trouvés
     */
    public function findByPeriod($startDate, $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return PaymentHistory[] Returns an array of PaymentHistory objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PaymentHistory
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
