<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Form\MembreType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

#[Route('/settings')]
class SettingsController extends AbstractController
{
    #[Route('/', name: 'app_settings', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger): Response
    {
        // Autoriser les utilisateurs avec ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour accéder aux paramètres.');
        }

        /** @var Membre|null $membre */
        $membre = $this->getUser();
        if (!$membre) {
            return $this->redirectToRoute('app_auth');
        }

        // Débogage : Vérifier l'état de l'entité avant de construire le formulaire
        if (is_null($membre->getNom()) || is_null($membre->getPrenom()) || is_null($membre->getEmail()) || 
            is_null($membre->getCin()) || is_null($membre->getNumTel()) || is_null($membre->getAdresse()) || 
            is_null($membre->getMotDePasse()) || is_null($membre->getRole())) {
            throw new \Exception('Un champ obligatoire est null dans l\'entité Membre : ' . json_encode([
                'nom' => $membre->getNom(),
                'prenom' => $membre->getPrenom(),
                'email' => $membre->getEmail(),
                'cin' => $membre->getCin(),
                'numTel' => $membre->getNumTel(),
                'adresse' => $membre->getAdresse(),
                'motDePasse' => $membre->getMotDePasse(),
                'role' => $membre->getRole(),
            ]));
        }

        // Utiliser MembreType pour le formulaire principal
        $form = $this->createForm(MembreType::class, $membre, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du mot de passe si modifié
            $motDePasse = $form->get('motDePasse')->getData();
            if ($motDePasse) {
                $membre->setMotDePasse(
                    $passwordHasher->hashPassword($membre, $motDePasse)
                );
            }

            $entityManager->persist($membre);
            $entityManager->flush();

            $this->addFlash('success', 'Vos paramètres ont été mis à jour avec succès !');
            return $this->redirectToRoute('app_settings');
        }

        // Formulaire séparé pour l'upload de la photo
        $imageForm = $this->createFormBuilder()
            ->add('image', FileType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k', // Limite la taille à 1 Mo
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF)',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Modifier la photo',
                'attr' => ['style' => 'display: none;'] // Cacher le bouton (on utilisera JavaScript pour soumettre)
            ])
            ->getForm();

        $imageForm->handleRequest($request);

        if ($imageForm->isSubmitted() && $imageForm->isValid()) {
            $imageFile = $imageForm->get('image')->getData();
            if ($imageFile) {
                // Générer un nom de fichier unique
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Déplacer le fichier dans le dossier public/uploads/images/
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                    return $this->redirectToRoute('app_settings');
                }

                // Supprimer l'ancienne image si elle existe
                if ($membre->getImage()) {
                    $oldImagePath = $this->getParameter('images_directory') . '/' . $membre->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Mettre à jour la propriété image du membre
                $membre->setImage($newFilename);

                // Enregistrer les modifications
                $entityManager->persist($membre);
                $entityManager->flush();

                $this->addFlash('success', 'Votre photo de profil a été mise à jour avec succès !');
                return $this->redirectToRoute('app_settings');
            }
        }

        return $this->render('settings/index.html.twig', [
            'form' => $form->createView(),
            'imageForm' => $imageForm->createView(),
        ]);
    }
}