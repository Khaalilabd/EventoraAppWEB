<?php

namespace App\Controller\Admin;

use App\Entity\PaymentHistory;
use App\Repository\PaymentHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/payment-history')]
#[IsGranted('ROLE_ADMIN')]
class PaymentHistoryController extends AbstractController
{
    #[Route('/', name: 'admin_payment_history_index', methods: ['GET'])]
    public function index(Request $request, PaymentHistoryRepository $paymentHistoryRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $paymentHistoryRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');
            
        // Filtrage par type de réservation
        $reservationType = $request->query->get('type');
        if ($reservationType) {
            $queryBuilder->andWhere('p.reservationType = :type')
                ->setParameter('type', $reservationType);
        }
        
        // Filtrage par date
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        
        if ($startDate) {
            $queryBuilder->andWhere('p.createdAt >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($startDate . ' 00:00:00'));
        }
        
        if ($endDate) {
            $queryBuilder->andWhere('p.createdAt <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
        }
        
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );
        
        // Calculer le montant total des paiements
        $totalAmount = $paymentHistoryRepository->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
            
        return $this->render('admin/payment_history/index.html.twig', [
            'pagination' => $pagination,
            'totalAmount' => $totalAmount,
        ]);
    }
    
    #[Route('/{id}', name: 'admin_payment_history_show', methods: ['GET'])]
    public function show(PaymentHistory $paymentHistory): Response
    {
        return $this->render('admin/payment_history/show.html.twig', [
            'payment' => $paymentHistory,
        ]);
    }
    
    #[Route('/stats/dashboard', name: 'admin_payment_stats_dashboard', methods: ['GET'])]
    public function dashboardStats(PaymentHistoryRepository $paymentHistoryRepository): Response
    {
        // Statistiques des paiements pour le dashboard
        $totalAmount = $paymentHistoryRepository->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
            
        $totalCount = $paymentHistoryRepository->count([]);
        
        // Paiements du mois en cours
        $startOfMonth = new \DateTimeImmutable('first day of this month midnight');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');
        
        $currentMonthAmount = $paymentHistoryRepository->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
            
        $currentMonthCount = $paymentHistoryRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
            
        // Répartition par type de réservation
        $paymentsByType = $paymentHistoryRepository->createQueryBuilder('p')
            ->select('p.reservationType, COUNT(p.id) as count, SUM(p.amount) as total')
            ->groupBy('p.reservationType')
            ->getQuery()
            ->getResult();
            
        return $this->render('admin/payment_history/dashboard_stats.html.twig', [
            'totalAmount' => $totalAmount,
            'totalCount' => $totalCount,
            'currentMonthAmount' => $currentMonthAmount,
            'currentMonthCount' => $currentMonthCount,
            'paymentsByType' => $paymentsByType,
        ]);
    }
} 