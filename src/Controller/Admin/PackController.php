<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pack;
use App\Entity\PackService;
use App\Entity\GService;
use App\Form\PackType;
use App\Repository\PackRepository;
use App\Repository\TypepackRepository;
use App\Service\PackDescriptionGenerator;
use App\Service\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;

#[Route("/admin/pack")]
final class PackController extends AbstractController
{
    private $slugger;
    private $emailNotificationService;

    public function __construct(
        SluggerInterface $slugger,
        EmailNotificationService $emailNotificationService
    ) {
        $this->slugger = $slugger;
        $this->emailNotificationService = $emailNotificationService;
    }

    #[Route('/', name: 'admin_packs', methods: ['GET'])]
public function index(Request $request, PackRepository $packRepository, TypepackRepository $typepackRepository): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    // Get sort, order, page, type, location, minPrice, and maxPrice parameters
    $sort = $request->query->get('sort', 'nomPack');
    $order = $request->query->get('order', 'ASC');
    $page = $request->query->getInt('page', 1);
    $type = $request->query->get('type', '');
    $location = $request->query->get('location', '');
    $minPriceRaw = $request->query->get('minPrice', '');
    $maxPriceRaw = $request->query->get('maxPrice', '');
    $limit = 3; // Packs per page

    // Convert price filters to float or null, treat 0 as null to disable filter
    $minPrice = $minPriceRaw !== '' ? (float) $minPriceRaw : null;
    $maxPrice = $maxPriceRaw !== '' ? (float) $maxPriceRaw : null;
    $minPrice = ($minPrice === 0.0) ? null : $minPrice;
    $maxPrice = ($maxPrice === 0.0) ? null : $maxPrice;

    // Validate sort field
    $validSortFields = ['nomPack', 'prix', 'nbrGuests'];
    if (!in_array($sort, $validSortFields)) {
        $sort = 'nomPack';
    }

    // Validate order
    if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
        $order = 'ASC';
    }

    // Validate location
    $validLocations = ['HOTEL', 'MAISON_D_HOTE', 'ESPACE_VERT', 'SALLE_DE_FETE', 'AUTRE'];
    if ($location && !in_array($location, $validLocations)) {
        $location = '';
    }

    // Fetch packs with pagination, sorting, and filters
    $result = $packRepository->findAllPaginated($page, $limit, $sort, $order, $type, $location, $minPrice, $maxPrice);
    $packs = $result['packs'];
    $totalPacks = $result['total'];
    $totalPages = ceil($totalPacks / $limit);

    // Fetch all typepacks for filter dropdown
    $typepacks = $typepackRepository->findAll();

    return $this->render('admin/Pack/index.html.twig', [
        'packs' => $packs,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'sort' => $sort,
        'order' => $order,
        'type' => $type,
        'location' => $location,
        'minPrice' => $minPrice,
        'maxPrice' => $maxPrice,
        'typepacks' => $typepacks,
    ]);
}

    #[Route('/new', name: 'admin_packs_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, PackRepository $packRepository, PackDescriptionGenerator $descriptionGenerator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pack = new Pack();
        $form = $this->createForm(PackType::class, $pack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for duplicate nomPack
            $existingPack = $packRepository->findOneBy(['nomPack' => $pack->getNomPack()]);
            if ($existingPack) {
                $this->addFlash('error', 'Ce nom de pack existe déjà.');
                return $this->render('admin/Pack/add.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Generate description if empty
            if (!$pack->getDescription()) {
                $newDescription = $descriptionGenerator->generateDescription($pack);
                $pack->setDescription($newDescription ?: sprintf(
                    'Description générique pour %s, un pack pour %d invités à %.2f €.',
                    $pack->getNomPack() ?? 'ce pack',
                    $pack->getNbrGuests() ?? 0,
                    $pack->getPrix() ?? 0.0
                ));
            }

            // Handle the image upload
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image_path')->getData();
            if ($imageFile) {
                $newFilename = $this->uploadImage($imageFile);
                $pack->setImagePath('/Uploads/images/' . $newFilename);
            }

            // Persist the pack
            $entityManager->persist($pack);
            $entityManager->flush();

            // Save selected services in pack_service
            $services = $form->get('services')->getData();
            foreach ($services as $service) {
                $packService = new PackService();
                $packService->setPack_id($pack->getId());
                $packService->setService_titre($service->getTitre());
                $entityManager->persist($packService);
            }
            $entityManager->flush();

            // Notify members of the new pack
            $this->emailNotificationService->notifyMembersOfNewPack($pack);

            $this->addFlash('success', 'Le pack a été ajouté avec succès et les membres ont été notifiés.');
            return $this->redirectToRoute('admin_packs');
        }

        return $this->render('admin/Pack/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_packs_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pack $pack, EntityManagerInterface $entityManager, PackRepository $packRepository, PackDescriptionGenerator $descriptionGenerator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        // Preload services for the form
        $currentServices = $entityManager->getRepository(PackService::class)->findBy(['pack_id' => $pack->getId()]);
        $serviceEntities = [];
        foreach ($currentServices as $packService) {
            $service = $entityManager->getRepository(GService::class)->findOneBy(['titre' => $packService->getService_titre()]);
            if ($service) {
                $serviceEntities[] = $service;
            }
        }
        $pack->setServices($serviceEntities);
    
        $form = $this->createForm(PackType::class, $pack);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Check for duplicate nomPack, excluding the current pack
            $existingPack = $packRepository->findOneBy(['nomPack' => $pack->getNomPack()]);
            if ($existingPack && $existingPack->getId() !== $pack->getId()) {
                $this->addFlash('error', 'Ce nom de pack existe déjà.');
                return $this->render('admin/Pack/edit.html.twig', [
                    'form' => $form->createView(),
                    'pack' => $pack,
                ]);
            }
    
            // Always generate a new description on form submission
            $newDescription = $descriptionGenerator->generateDescription($pack);
            $pack->setDescription($newDescription ?? sprintf(
                'Description générique pour %s, un pack pour %d invités à %.2f €.',
                $pack->getNomPack() ?? 'ce pack',
                $pack->getNbrGuests() ?? 0,
                $pack->getPrix() ?? 0.0
            ));
    
            // Handle the image upload
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image_path')->getData();
            if ($imageFile) {
                $newFilename = $this->uploadImage($imageFile);
                $pack->setImagePath('/Uploads/images/' . $newFilename);
            }
    
            // Update pack
            $entityManager->persist($pack);
            $entityManager->flush();
    
            // Update pack_service entries
            // Remove existing pack_service entries
            $existingPackServices = $entityManager->getRepository(PackService::class)->findBy(['pack_id' => $pack->getId()]);
            foreach ($existingPackServices as $packService) {
                $entityManager->remove($packService);
                $entityManager->detach($packService);
            }
            $entityManager->flush();
            $entityManager->clear(PackService::class);
    
            // Add new pack_service entries
            $services = $form->get('services')->getData();
            foreach ($services as $service) {
                $packService = new PackService();
                $packService->setPack_id($pack->getId());
                $packService->setService_titre($service->getTitre());
                $entityManager->persist($packService);
            }
    
            $entityManager->flush();
    
            $this->addFlash('success', 'Le pack a été modifié avec succès.');
            return $this->redirectToRoute('admin_packs');
        }
    
        return $this->render('admin/Pack/edit.html.twig', [
            'form' => $form->createView(),
            'pack' => $pack,
        ]);
    }

    #[Route('/{id}', name: 'admin_packs_delete', methods: ['POST'])]
    public function delete(Request $request, Pack $pack, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$pack->getId(), $request->request->get('_token'))) {
            // Remove associated PackService entries
            $packServices = $entityManager->getRepository(PackService::class)->findBy(['pack_id' => $pack->getId()]);
            foreach ($packServices as $packService) {
                $entityManager->remove($packService);
            }

            // Remove the Pack
            $entityManager->remove($pack);
            $entityManager->flush();

            $this->addFlash('success', 'Le pack a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_packs');
    }

    private function uploadImage(UploadedFile $imageFile): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move(
                $this->getParameter('images_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
            throw new \Exception('Failed to upload image: ' . $e->getMessage());
        }

        return $newFilename;
    }
}