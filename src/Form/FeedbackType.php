<?php

namespace App\Controller\Users;

use App\Entity\Feedback;
use App\Entity\Membre;
use App\Form\FeedbackType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/feedback')]
class UserFeedbackController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/new', name: 'app_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Début de la méthode new pour feedback', [
            'isAjax' => $request->isXmlHttpRequest(),
            'method' => $request->getMethod(),
        ]);

        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être un membre ou un administrateur pour soumettre un feedback.'
                ], 403);
            }
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour soumettre un feedback.');
        }

        $user = $this->getUser();
        if (!$user) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non connecté.'
                ], 401);
            }
            $this->addFlash('error', 'Vous devez être connecté pour soumettre un feedback.');
            return $this->redirectToRoute('app_login');
        }

        $membre = $entityManager->getRepository(Membre::class)->findOneBy(['email' => $user->getEmail()]);
        if (!$membre) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Aucun membre correspondant trouvé pour cet utilisateur.'
                ], 400);
            }
            $this->addFlash('error', 'Aucun membre correspondant trouvé pour cet utilisateur.');
            return $this->redirectToRoute('app_home');
        }

        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    try {
                        $feedback->setMembre($membre);
                        $feedback->setDate(new \DateTime());

                        $souvenirsFile = $form->get('souvenirsFile')->getData();
                        if ($souvenirsFile) {
                            $this->logger->info('Image uploadée', [
                                'filename' => $souvenirsFile->getClientOriginalName(),
                                'size' => $souvenirsFile->getSize(),
                                'mimeType' => $souvenirsFile->getMimeType(),
                            ]);

                            $binaryContent = file_get_contents($souvenirsFile->getPathname());
                            $feedback->setSouvenirs($binaryContent);
                        }

                        $entityManager->persist($feedback);
                        $entityManager->flush();

                        $this->logger->info('Feedback enregistré avec succès', [
                            'feedback_id' => $feedback->getID(),
                        ]);

                        return new JsonResponse(['success' => true]);
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de la soumission du feedback', [
                            'exception' => $e->getMessage(),
                            'stack_trace' => $e->getTraceAsString(),
                        ]);

                        return new JsonResponse([
                            'success' => false,
                            'errors' => ['souvenirsFile' => ['Erreur lors de l\'upload : ' . $e->getMessage()]]
                        ], 500);
                    }
                }

                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $fieldName = $error->getOrigin()->getName();
                    $errors[$fieldName][] = $error->getMessage();
                }

                $this->logger->error('Erreurs de validation du formulaire', [
                    'errors' => $errors,
                    'form_data' => $request->request->all(),
                    'files' => $request->files->all(),
                ]);

                return new JsonResponse(['success' => false, 'errors' => $errors]);
            }

            $this->logger->warning('Requête AJAX invalide');
            return new JsonResponse([
                'success' => false,
                'message' => 'Requête invalide.'
            ], 400);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $feedback->setMembre($membre);
                $feedback->setDate(new \DateTime());

                $souvenirsFile = $form->get('souvenirsFile')->getData();
                if ($souvenirsFile) {
                    $binaryContent = file_get_contents($souvenirsFile->getPathname());
                    $feedback->setSouvenirs($binaryContent);
                }

                $entityManager->persist($feedback);
                $entityManager->flush();

                $this->addFlash('success', 'Votre feedback a été soumis avec succès !');
                return $this->redirectToRoute('app_home');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la soumission du feedback (non-AJAX)', [
                    'exception' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
            }
        }

        return $this->render('feedback/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}