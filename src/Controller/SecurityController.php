<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/auth', name: 'app_auth', methods: ['GET', 'POST'])]
    public function auth(
        AuthenticationUtils $authenticationUtils,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Formulaire de connexion uniquement
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
}