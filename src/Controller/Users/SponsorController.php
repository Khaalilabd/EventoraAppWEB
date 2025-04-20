<?php

namespace App\Controller\Users;
use App\Entity\Sponsor;
use App\Repository\SponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route("/user/sponsor")]
final class SponsorController extends AbstractController
{
    #[Route('/', name: 'user_sponsor', methods: ['GET'])]
    public function index(SponsorRepository $SponsorRepository): Response
    {
        $Sponsor = $SponsorRepository->findAll();
       

       return $this->render('user/sponsor/index.html.twig', [
           'Sponsor' => $Sponsor,
       ]);
    }
}
