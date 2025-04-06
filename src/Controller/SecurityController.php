<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SecurityController extends AbstractController
{
    #[Route('/auth', name: 'app_auth', methods: ['GET', 'POST'])]
    public function auth(
        AuthenticationUtils $authenticationUtils,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $formConnexion = $this->createForm(LoginFormType::class);
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('security/auth.html.twig', [
            'registration_form' => $this->createForm(RegistrationFormType::class)->createView(),
            'login_form' => $formConnexion->createView(),
            'error' => $error,
            'csrf_token' => $csrfTokenManager->getToken('authenticate')->getValue(),
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $membre = new Membre();
        $form = $this->createForm(RegistrationFormType::class, $membre);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Hacher le mot de passe
                    $plainPassword = $form->get('motDePasse')->getData();
                    if (!$plainPassword) {
                        throw new \Exception('Le mot de passe ne peut pas être vide.');
                    }
                    $hashedPassword = $userPasswordHasher->hashPassword($membre, $plainPassword);
                    $membre->setMotDePasse($hashedPassword);

                    // Définir un rôle par défaut
                    $membre->setRole('MEMBRE');
                    $membre->setIsConfirmed(false);

                    // Gérer l'upload de l'image
                    $imageFile = $form->get('image')->getData();
                    if ($imageFile) {
                        $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                        $membre->setImage($newFilename);
                    }

                    // Enregistrer dans la base de données
                    $entityManager->persist($membre);
                    $entityManager->flush();

                    $this->addFlash('success', 'Votre compte a été créé avec succès !');
                    return $this->redirectToRoute('app_auth');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'inscription : ' . $e->getMessage());
                }
            } else {
                // Afficher les erreurs de validation
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'registration_form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/login-check', name: 'login_check')]
    public function checkLogin(Security $security): Response
    {
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->getUser();
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('admin_dashboard');
        }
        return $this->redirectToRoute('app_auth');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function adminDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password_request', methods: ['GET', 'POST'])]
    public function requestResetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        \Psr\Log\LoggerInterface $logger
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'email saisi dans le formulaire
            $email = $form->get('email')->getData();
            $logger->info('Email saisi dans le formulaire : ' . $email);

            // Rechercher l'utilisateur avec cet email
            $membre = $entityManager->getRepository(Membre::class)->findOneBy(['email' => $email]);

            if ($membre) {
                $logger->info('Utilisateur trouvé : ' . $membre->getEmail());
                // Générer un token simplifié (alphanumérique, 32 caractères)
                $token = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 32);
                $logger->info('Token généré : ' . $token);
                $membre->setToken($token);
                $membre->setTokenExpiration(new \DateTime('+1 hour')); // Expire dans 1 heure
                $entityManager->persist($membre);

                try {
                    $entityManager->flush();
                    $logger->info('Token enregistré avec succès.');
                } catch (\Exception $e) {
                    $logger->error('Erreur lors de l\'enregistrement du token : ' . $e->getMessage());
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement du token : ' . $e->getMessage());
                    return $this->redirectToRoute('app_auth');
                }

                // Vérifier que le token est bien enregistré dans la base de données
                $membreAfterFlush = $entityManager->getRepository(Membre::class)->findOneBy(['email' => $email]);
                $logger->info('Token après flush : ' . ($membreAfterFlush->getToken() ?? 'NULL'));
                $logger->info('Date d\'expiration après flush : ' . ($membreAfterFlush->getTokenExpiration() ? $membreAfterFlush->getTokenExpiration()->format('Y-m-d H:i:s') : 'NULL'));

                // Envoyer un email avec le lien de réinitialisation
                $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
                $logger->info('Lien de réinitialisation généré : ' . $resetLink);
                $emailMessage = (new Email())
                    ->from('hediasnoussi2@gmail.com') // Expéditeur fixé à hediasnoussi2@gmail.com
                    ->to($email) // Destinataire : l'email saisi dans le formulaire
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html("Cliquez sur ce lien pour réinitialiser votre mot de passe : <a href='$resetLink'>$resetLink</a>");

                try {
                    $logger->info('Envoi d\'un email de réinitialisation à : ' . $email);
                    $mailer->send($emailMessage);
                    $this->addFlash('success', 'Un email de réinitialisation a été envoyé à ' . $email . '.');
                } catch (\Exception $e) {
                    $logger->error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_auth');
            } else {
                $logger->error('Aucun utilisateur trouvé avec l\'email : ' . $email);
                $this->addFlash('error', 'Aucun compte trouvé avec cet email.');
            }
        }

        return $this->render('security/reset_password_request.html.twig', [
            'request_form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        \Psr\Log\LoggerInterface $logger
    ): Response {
        $logger->info('Token reçu dans l\'URL : ' . $token);

        // Rechercher l'utilisateur avec le token
        $membre = $entityManager->getRepository(Membre::class)->findOneBy(['token' => $token]);
        
        if (!$membre) {
            $logger->error('Aucun utilisateur trouvé avec le token : ' . $token);
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_auth');
        }

        // Vérifier si le token correspond
        $storedToken = $membre->getToken();
        $logger->info('Token stocké dans la base de données : ' . ($storedToken ?? 'NULL'));
        $logger->info('Comparaison des tokens : URL=[' . $token . '], DB=[' . ($storedToken ?? 'NULL') . ']');

        $tokenExpiration = $membre->getTokenExpiration();
        $logger->info('Date d\'expiration du token : ' . ($tokenExpiration ? $tokenExpiration->format('Y-m-d H:i:s') : 'NULL'));

        if ($storedToken !== $token) {
            $logger->error('Les tokens ne correspondent pas : URL=[' . $token . '], DB=[' . ($storedToken ?? 'NULL') . ']');
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_auth');
        }

        if ($tokenExpiration && $tokenExpiration < new \DateTime()) {
            $logger->error('Token expiré : Date d\'expiration=' . $tokenExpiration->format('Y-m-d H:i:s'));
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $hashedPassword = $userPasswordHasher->hashPassword($membre, $newPassword);
            $membre->setMotDePasse($hashedPassword);
            $membre->setToken(null);
            $membre->setTokenExpiration(null); // Réinitialiser l'expiration
            $entityManager->persist($membre);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('security/reset_password.html.twig', [
            'reset_form' => $form->createView(),
        ]);
    }
}