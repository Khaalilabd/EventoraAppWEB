<?php


namespace App\Controller\Admin;
use App\Entity\Sponsor;
use App\Form\SponsorsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

#[Route("/admin/sponsors")]
final class SponsorsController extends AbstractController
{
    #[Route('/', name: 'admin_sponsors' , methods:['Get'])]
    public function index(SponsorRepository $SponsorRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('sponsors/index.html.twig', [
            'controller_name' => 'SponsorsController',
        ]);
    }
}
