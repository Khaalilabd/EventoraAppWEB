<?php

namespace App\Controller\Users;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/feedback')]
class UserFeedbackController extends AbstractController
{
    #[Route('/new', name: 'app_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MEMBRE');

        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        // Vérifier si la requête est une soumission AJAX
        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                // Associer le feedback à l'utilisateur connecté
                $feedback->setMembre($this->getUser());

                // Définir la date du système
                $feedback->setDate(new \DateTime());

                // Gérer l'upload de l'image
                $souvenirsFile = $feedback->getSouvenirsFile();
                if ($souvenirsFile) {
                    try {
                        // Lire le contenu binaire de l'image
                        $binaryContent = file_get_contents($souvenirsFile->getPathname());
                        $feedback->setSouvenirs($binaryContent);
                    } catch (\Exception $e) {
                        return new JsonResponse([
                            'success' => false,
                            'errors' => ['souvenirsFile' => ['Une erreur est survenue lors de l\'upload de l\'image.']]
                        ]);
                    }
                }

                // Enregistrer le feedback
                $entityManager->persist($feedback);
                $entityManager->flush();

                // Renvoyer une réponse JSON de succès
                return new JsonResponse(['success' => true]);
            }

            // Si le formulaire contient des erreurs, les collecter
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $fieldName = $error->getOrigin()->getName(); // Nom du champ (Vote, Description, etc.)
                $errors[$fieldName][] = $error->getMessage();
            }

            // Renvoyer une réponse JSON avec les erreurs
            return new JsonResponse(['success' => false, 'errors' => $errors]);
        }

        // Si ce n'est pas une requête AJAX, renvoyer la vue (GET ou autre cas)
        return $this->render('admin/feedback/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}