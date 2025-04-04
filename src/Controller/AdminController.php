<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Données statiques pour l'exemple (remplacez par des requêtes Doctrine)
        $activeUsers = 1600;
        $activeUsersGrowth = 55;
        $totalProjects = 50;
        $projectsDone = 30;
        $projects = [
            ['name' => 'Project A', 'members' => ['Alice', 'Bob'], 'budget' => '$10,000', 'completion' => 75],
            ['name' => 'Project B', 'members' => ['Charlie'], 'budget' => '$5,000', 'completion' => 40],
        ];
        $positiveReviews = 80;
        $totalOrders = 120;

        return $this->render('admin/dashboard.html.twig', [
            'active_users' => $activeUsers,
            'active_users_growth' => $activeUsersGrowth,
            'total_projects' => $totalProjects,
            'projects_done' => $projectsDone,
            'projects' => $projects,
            'positive_reviews' => $positiveReviews,
            'total_orders' => $totalOrders,
        ]);
    }

    #[Route('/admin/tables', name: 'admin_tables')]
    public function tables(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return new Response('Tables page - to be implemented');
    }
}