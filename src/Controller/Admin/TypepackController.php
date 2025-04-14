<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Typepack;
use App\Form\TypepackType;
use App\Repository\TypepackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface; // Correct namespace
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/admin/typepack")]
final class TypepackController extends AbstractController
{
    #[Route('/', name: 'admin_typepacks', methods: ['GET'])]
    public function index(TypepackRepository $typepackRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $typepacks = $typepackRepository->findAll();

        return $this->render('admin/Typepack/index.html.twig', [
            'typepacks' => $typepacks,
        ]);
    }

    #[Route('/add', name: 'admin_typepacks_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $typepack = new Typepack();
        $form = $this->createForm(TypepackType::class, $typepack);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($typepack);
            if ($form->isValid() && count($errors) === 0) {
                $entityManager->persist($typepack);
                $entityManager->flush();
                $this->addFlash('success', 'Le type de pack a été ajouté avec succès.');
                return $this->redirectToRoute('admin_typepacks');
            } else {
                $logger->debug('Form validation errors:', ['errors' => (string) $errors]);
                $duplicateError = false;
                foreach ($errors as $error) {
                    if ($error->getMessage() === 'Ce type existe déjà.') {
                        $duplicateError = true;
                        break;
                    }
                }

                if ($duplicateError) {
                    $this->addFlash('warning', 'Un type de pack avec ce nom existe déjà. Veuillez choisir un nom différent.');
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'ajout du type de pack. Veuillez vérifier les informations saisies.');
                }
            }
        }

        return $this->render('admin/Typepack/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_typepacks_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Typepack $typepack, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$typepack) {
            throw $this->createNotFoundException('Type de pack non trouvé.');
        }

        $form = $this->createForm(TypepackType::class, $typepack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le type de pack a été modifié avec succès.');
            return $this->redirectToRoute('admin_typepacks');
        }

        return $this->render('admin/Typepack/edit.html.twig', [
            'form' => $form->createView(),
            'typepack' => $typepack,
        ]);
    }

    #[Route('/{id}', name: 'admin_typepacks_delete', methods: ['POST'])]
    public function delete(Request $request, ?Typepack $typepack, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$typepack) {
            throw $this->createNotFoundException('Type de pack non trouvé.');
        }

        if ($this->isCsrfTokenValid('delete' . $typepack->getId(), $request->request->get('_token'))) {
            $entityManager->remove($typepack);
            $entityManager->flush();
            $this->addFlash('success', 'Le type de pack et tous les packs associés ont été supprimés avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression : jeton CSRF invalide.');
        }

        return $this->redirectToRoute('admin_typepacks');
    }
}