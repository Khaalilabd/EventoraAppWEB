<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Typepack;
use App\Repository\TypepackRepository;
use Doctrine\ORM\EntityManagerInterface;

#[Route("/admin/typepack")]
final class TypepackController extends AbstractController
{
    #[Route('/', name: 'admin_typepacks', methods: ['GET'])]
    public function index(TypepackRepository $typepackRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $typepacks = $typepackRepository->findAll();

        return $this->render('admin/Typepack/index.html.twig', [
            'typepacks' => $typepacks,
        ]);
    }

    #[Route('/{id}', name: 'admin_typepacks_delete', methods: ['POST'])]
    public function delete(Request $request, Typepack $typepack, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $typepack->getId(), $request->request->get('_token'))) {
           
            $entityManager->remove($typepack);
            $entityManager->flush();

            $this->addFlash('success', 'Le type de pack et tous les packs associés ont été supprimés avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression : jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_typepacks');
    }
}