<?php

namespace App\Controller\Users;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reclamation')]
class UserReclamationController extends AbstractController
{
    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MEMBRE');
    
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            // Récupérer les données brutes du formulaire
            $data = $form->getData();
    
            // Initialiser un tableau pour stocker les erreurs
            $errors = [];
    
            // Contrôle manuel du champ "titre"
            if (empty($data->getTitre()) || strlen($data->getTitre()) < 3) {
                $errors['titre'] = 'Le titre est requis et doit contenir au moins 3 caractères.';
            } elseif (strlen($data->getTitre()) > 255) {
                $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
            }
    
            // Contrôle manuel du champ "description"
            if (empty($data->getDescription()) || strlen($data->getDescription()) < 10) {
                $errors['description'] = 'La description est requise et doit contenir au moins 10 caractères.';
            }
    
            // Contrôle manuel du champ "Type"
            if (empty($data->getType()) || !in_array($data->getType(), Reclamation::TYPES)) {
                $errors['Type'] = 'Veuillez sélectionner un type valide.';
            }
    
            // Si aucune erreur, on persiste les données
            if (empty($errors)) {
                $reclamation->setMembre($this->getUser());
                $reclamation->setStatut(Reclamation::STATUT_EN_ATTENTE);
                $entityManager->persist($reclamation);
                $entityManager->flush();
    
                $this->addFlash('success', 'Votre réclamation a été soumise avec succès !');
                return $this->redirectToRoute('app_home', ['_fragment' => 'fh5co-started']);
            } else {
                // Ajouter les erreurs au formulaire pour affichage dans Twig
                foreach ($errors as $field => $message) {
                    $form->get($field)->addError(new \Symfony\Component\Form\FormError($message));
                }
            }
        }
    
        return $this->render('admin/reclamations/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}