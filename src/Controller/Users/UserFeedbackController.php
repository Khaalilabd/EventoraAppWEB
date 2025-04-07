<?php

namespace App\Controller\Users;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer le feedback à l'utilisateur connecté
            $feedback->setMembre($this->getUser());

            // Gérer l'upload de l'image
            $souvenirsFile = $feedback->getSouvenirsFile();
            if ($souvenirsFile) {
                try {
                    // Lire le contenu binaire de l'image
                    $binaryContent = file_get_contents($souvenirsFile->getPathname());
                    $feedback->setSouvenirs($binaryContent);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
                    return $this->redirectToRoute('app_feedback_new');
                }
            }

            // Enregistrer le feedback
            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Votre feedback a été soumis avec succès !');
            return $this->redirectToRoute('app_home', ['_fragment' => 'fh5co-started']);
        }

        return $this->render('admin/feedback/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}