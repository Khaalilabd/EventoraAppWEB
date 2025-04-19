<?php

namespace App\Controller\Users;

use App\Entity\Reclamation;
use App\Entity\Membre;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/reclamation')]
class UserReclamationController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Autoriser les utilisateurs avec ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            $this->logger->warning('Accès refusé : utilisateur non autorisé', [
                'roles' => $this->getUser() ? $this->getUser()->getRoles() : 'aucun utilisateur',
            ]);
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
            $this->logger->warning('Utilisateur non connecté');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non connecté.'
                ], 401);
            }
            $this->addFlash('error', 'Vous devez être connecté pour soumettre une réclamation.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier que l'utilisateur est un Membre
        if (!$user instanceof Membre) {
            $this->logger->error('L\'utilisateur connecté n\'est pas une instance de Membre', [
                'user_class' => get_class($user),
                'user_id' => $user->getId(),
            ]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur invalide pour soumettre une réclamation.'
                ], 400);
            }
            $this->addFlash('error', 'Utilisateur invalide pour soumettre une réclamation.');
            return $this->redirectToRoute('app_home');
        }

        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    try {
                        $reclamation->setMembre($user);
                        $reclamation->setStatut(Reclamation::STATUT_EN_ATTENTE);
                        $entityManager->persist($reclamation);
                        $entityManager->flush();

                        $this->logger->info('Réclamation enregistrée avec succès', [
                            'reclamation_id' => $reclamation->getId(),
                        ]);

                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Votre réclamation a été soumise avec succès !'
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de la soumission de la réclamation', [
                            'exception' => $e->getMessage(),
                            'stack_trace' => $e->getTraceAsString(),
                        ]);
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Erreur lors de la soumission : ' . $e->getMessage()
                        ], 500);
                    }
                }

                // Récupérer les erreurs de validation
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $fieldName = $error->getOrigin()->getName();
                    $errors[$fieldName][] = $error->getMessage();
                }

                $this->logger->error('Erreurs de validation du formulaire', [
                    'errors' => $errors,
                ]);

                return new JsonResponse([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Veuillez corriger les erreurs dans le formulaire.'
                ], 400);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'Requête invalide.'
            ], 400);
        }

        // Gestion de la requête classique (non-AJAX)
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $reclamation->setMembre($user);
                $reclamation->setStatut(Reclamation::STATUT_EN_ATTENTE);
                $entityManager->persist($reclamation);
                $entityManager->flush();

                $this->addFlash('success', 'Votre réclamation a été soumise avec succès !');
                return $this->redirectToRoute('app_home', ['_fragment' => 'fh5co-started']);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la soumission de la réclamation (non-AJAX)', [
                    'exception' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', 'Erreur lors de la soumission : ' . $e->getMessage());
            }
        }

        return $this->render('admin/reclamations/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}