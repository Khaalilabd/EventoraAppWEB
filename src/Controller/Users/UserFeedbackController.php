<?php

namespace App\Controller\Users;

use App\Entity\Feedback;
use App\Entity\Membre;
use App\Form\FeedbackType;
use App\Service\BrevoEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Twig\Environment;

#[Route('/feedback')]
class UserFeedbackController extends AbstractController
{
    private $logger;
    private $twig;
    private $emailSender;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        BrevoEmailSender $emailSender
    ) {
        $this->logger = $logger;
        $this->twig = $twig;
        $this->emailSender = $emailSender;
    }

    #[Route('/new', name: 'app_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Début de la méthode new pour feedback', [
            'isAjax' => $request->isXmlHttpRequest(),
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
            'request_data' => $request->request->all(),
            'files' => $request->files->all(),
        ]);

        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            $this->logger->warning('Accès refusé : utilisateur non autorisé', [
                'roles' => $this->getUser() ? $this->getUser()->getRoles() : 'aucun utilisateur',
            ]);
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
            $this->logger->warning('Utilisateur non connecté');
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
            $this->logger->warning('Aucun membre correspondant trouvé', [
                'email' => $user->getEmail(),
            ]);
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
        $feedback->setMembre($membre);
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            $this->logger->info('Requête AJAX reçue', [
                'form_submitted' => $form->isSubmitted(),
                'form_valid' => $form->isValid(),
                'form_data' => $request->request->all(),
                'files' => $request->files->all(),
            ]);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
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

                    // Envoyer un email à l'utilisateur
                    $membreHtml = $this->twig->render('admin/feedback/email_feedback_new_membre.html.twig', [
                        'feedback' => $feedback,
                        'membre' => $membre,
                    ]);
                    $emailSentMembre = $this->emailSender->sendEmail(
                        $membre->getEmail(),
                        $membre->getNom() . ' ' . $membre->getPrenom(),
                        'Confirmation de votre feedback - Eventora',
                        $membreHtml
                    );
                    $this->logger->info('Envoi email membre après création feedback', [
                        'email' => $membre->getEmail(),
                        'success' => $emailSentMembre,
                        'feedback_id' => $feedback->getID(),
                    ]);

                    if (!$emailSentMembre) {
                        $this->logger->warning('Échec de l\'envoi de l\'email au membre après création feedback', [
                            'email' => $membre->getEmail(),
                            'feedback_id' => $feedback->getID(),
                        ]);
                    }

                    // Envoyer un email à tous les admins
                    $admins = $entityManager->getRepository(Membre::class)->findByRole('ADMIN');
                    $this->logger->info('Admins trouvés pour envoi email après création feedback', [
                        'admin_emails' => array_map(fn($admin) => $admin->getEmail(), $admins),
                        'feedback_id' => $feedback->getID(),
                    ]);
                    $emailSentAdmins = true;
                    foreach ($admins as $admin) {
                        try {
                            $adminHtml = $this->twig->render('admin/feedback/email_feedback_new_admin.html.twig', [
                                'feedback' => $feedback,
                                'membre' => $membre,
                                'admin' => $admin,
                            ]);
                            $emailSent = $this->emailSender->sendEmail(
                                $admin->getEmail(),
                                $admin->getNom() . ' ' . $admin->getPrenom(),
                                'Nouveau feedback soumis - Eventora',
                                $adminHtml
                            );
                            $this->logger->info('Envoi email admin après création feedback', [
                                'email' => $admin->getEmail(),
                                'success' => $emailSent,
                                'feedback_id' => $feedback->getID(),
                            ]);
                            if (!$emailSent) {
                                $emailSentAdmins = false;
                                $this->logger->warning('Échec de l\'envoi de l\'email à l\'admin après création feedback', [
                                    'email' => $admin->getEmail(),
                                    'feedback_id' => $feedback->getID(),
                                ]);
                            }
                        } catch (\Exception $e) {
                            $this->logger->error('Erreur lors de l\'envoi de l\'email à l\'admin après création feedback', [
                                'email' => $admin->getEmail(),
                                'feedback_id' => $feedback->getID(),
                                'exception' => $e->getMessage(),
                            ]);
                            $emailSentAdmins = false;
                        }
                    }

                    // Retourner l'URL de redirection vers la page des feedbacks
                    $redirectUrl = $this->generateUrl('app_user_history_feedbacks');

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Feedback soumis avec succès !' . (!$emailSentMembre || !$emailSentAdmins ? ' Cependant, certains emails n\'ont pas pu être envoyés.' : ''),
                        'redirectUrl' => $redirectUrl
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de la soumission du feedback', [
                        'exception' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString(),
                    ]);

                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Erreur serveur lors de la soumission : ' . $e->getMessage(),
                    ], 500);
                }
            }

            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $fieldName = $error->getOrigin() ? $error->getOrigin()->getName() : 'general';
                $errors[$fieldName] = [$error->getMessage()];
            }

            $this->logger->warning('Erreurs de validation détectées', [
                'errors' => $errors,
                'form_data' => $request->request->all(),
                'files' => $request->files->all(),
            ]);

            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
                'message' => 'Veuillez corriger les erreurs dans le formulaire.',
                'form_data' => $request->request->all(),
            ], 400);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $souvenirsFile = $form->get('souvenirsFile')->getData();
                if ($souvenirsFile) {
                    $binaryContent = file_get_contents($souvenirsFile->getPathname());
                    $feedback->setSouvenirs($binaryContent);
                }

                $entityManager->persist($feedback);
                $entityManager->flush();

                // Envoyer un email à l'utilisateur
                $membreHtml = $this->twig->render('admin/feedback/email_feedback_new_membre.html.twig', [
                    'feedback' => $feedback,
                    'membre' => $membre,
                ]);
                $emailSentMembre = $this->emailSender->sendEmail(
                    $membre->getEmail(),
                    $membre->getNom() . ' ' . $membre->getPrenom(),
                    'Confirmation de votre feedback - Eventora',
                    $membreHtml
                );
                $this->logger->info('Envoi email membre après création feedback (non-AJAX)', [
                    'email' => $membre->getEmail(),
                    'success' => $emailSentMembre,
                    'feedback_id' => $feedback->getID(),
                ]);

                if (!$emailSentMembre) {
                    $this->logger->warning('Échec de l\'envoi de l\'email au membre après création feedback (non-AJAX)', [
                        'email' => $membre->getEmail(),
                        'feedback_id' => $feedback->getID(),
                    ]);
                    $this->addFlash('warning', 'Feedback soumis, mais l\'email de confirmation n\'a pas pu être envoyé.');
                }

                // Envoyer un email à tous les admins
                $admins = $entityManager->getRepository(Membre::class)->findByRole('ADMIN');
                $this->logger->info('Admins trouvés pour envoi email après création feedback (non-AJAX)', [
                    'admin_emails' => array_map(fn($admin) => $admin->getEmail(), $admins),
                    'feedback_id' => $feedback->getID(),
                ]);
                $emailSentAdmins = true;
                foreach ($admins as $admin) {
                    try {
                        $adminHtml = $this->twig->render('admin/feedback/email_feedback_new_admin.html.twig', [
                            'feedback' => $feedback,
                            'membre' => $membre,
                            'admin' => $admin,
                        ]);
                        $emailSent = $this->emailSender->sendEmail(
                            $admin->getEmail(),
                            $admin->getNom() . ' ' . $admin->getPrenom(),
                            'Nouveau feedback soumis - Eventora',
                            $adminHtml
                        );
                        $this->logger->info('Envoi email admin après création feedback (non-AJAX)', [
                            'email' => $admin->getEmail(),
                            'success' => $emailSent,
                            'feedback_id' => $feedback->getID(),
                        ]);
                        if (!$emailSent) {
                            $emailSentAdmins = false;
                            $this->logger->warning('Échec de l\'envoi de l\'email à l\'admin après création feedback (non-AJAX)', [
                                'email' => $admin->getEmail(),
                                'feedback_id' => $feedback->getID(),
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de l\'envoi de l\'email à l\'admin après création feedback (non-AJAX)', [
                            'email' => $admin->getEmail(),
                            'feedback_id' => $feedback->getID(),
                            'exception' => $e->getMessage(),
                        ]);
                        $emailSentAdmins = false;
                    }
                }

                if (!$emailSentAdmins) {
                    $this->addFlash('warning', 'Feedback soumis, mais certains emails aux administrateurs n\'ont pas pu être envoyés.');
                }

                $this->addFlash('success', 'Votre feedback a été soumis avec succès !');
                return $this->redirectToRoute('app_user_history_feedbacks');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la soumission du feedback (non-AJAX)', [
                    'exception' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
            }
        }

        return $this->render('admin/feedback/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}