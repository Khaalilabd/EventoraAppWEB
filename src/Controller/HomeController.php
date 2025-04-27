<?php

namespace App\Controller;

use App\Repository\FeedbackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        $feedbacks = [];

        try {
            // Essayer findRandomFeedbacks
            $feedbacks = $feedbackRepository->findRandomFeedbacks(10);

            // Si vide, essayer une requête simple comme fallback
            if (empty($feedbacks)) {
                $feedbacks = $feedbackRepository->createQueryBuilder('f')
                    ->leftJoin('f.membre', 'm')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();
            }
        } catch (\Exception $e) {
            // Loguer l'erreur pour débogage
            $this->addFlash('error', 'Erreur lors de la récupération des feedbacks : ' . $e->getMessage());
            $feedbacks = [];
        }

        dump($feedbacks); // Débogage : vérifier ce que contient $feedbacks

        return $this->render('home.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }
}