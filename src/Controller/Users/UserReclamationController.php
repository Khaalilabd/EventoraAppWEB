<?php

namespace App\Controller\Users;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reclamation')]
class UserReclamationController extends AbstractController
{
    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Autoriser les utilisateurs avec ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être un membre ou un administrateur pour soumettre une réclamation.'
                ], 403);
            }
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour soumettre une réclamation.');
        }

        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non connecté.'
                ], 401);
            }
            $this->addFlash('error', 'Vous devez être connecté pour soumettre une réclamation.');
            return $this->redirectToRoute('app_login');
        }

        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $reclamation->setMembre($user);
                    $reclamation->setStatut(Reclamation::STATUT_EN_ATTENTE);
                    $entityManager->persist($reclamation);
                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Votre réclamation a été soumise avec succès !'
                    ]);
                }

                // Récupérer les erreurs de validation
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[$error->getOrigin()->getName()][] = $error->getMessage();
                }

                return new JsonResponse([
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'Requête invalide.'
            ], 400);
        }

        // Gestion de la requête classique (non-AJAX)
        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setMembre($user);
            $reclamation->setStatut(Reclamation::STATUT_EN_ATTENTE);
            $entityManager->persist($reclamation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réclamation a été soumise avec succès !');
            return $this->redirectToRoute('app_home', ['_fragment' => 'fh5co-started']);
        }

        // Rendre le template correct
        return $this->render('admin/reclamations/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}