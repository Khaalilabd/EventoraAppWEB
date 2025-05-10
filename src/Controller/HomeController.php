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
        $feedbacks = $feedbackRepository->findRandomFeedbacks(10);

        if (empty($feedbacks)) {
            $this->addFlash('warning', 'Aucun feedback disponible pour le moment.');
        }

        return $this->render('home/home.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }
}