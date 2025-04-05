<?php

namespace App\Controller\Admin;
use App\Entity\GService;
use App\Form\GServiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;


#[Route("/admin/service")]
class ServicesController extends AbstractController
{
    #[Route('/', name: 'admin_services', methods: ['GET'])]
    public function index(GServiceRepository $GServiceRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $GServices = $GServiceRepository->findAll();

        return $this->render('admin/service/index.html.twig', [
            'GServices' => $GServices,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_service_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, GService $GService, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');
    $form = $this->createForm(GServiceType::class, data: $GService);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        return $this->redirectToRoute('admin_services', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('admin/service/edit.html.twig', [
        'GService' => $GService,  // Passe GService à la vue
        'form' => $form->createView(),
    ]);
}


#[Route('/{id}/delete', name: 'admin_service_delete', methods: ['POST'])]
public function delete(Request $request, GService $gService, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');
    if ($this->isCsrfTokenValid('delete' . $gService->getId(), $request->request->get('_token'))) {
        $entityManager->remove($gService);
        $entityManager->flush();
        $this->addFlash('success', message: 'Service supprimée avec succès.');
    }else {
        $this->addFlash('error', 'Token CSRF invalide.');
    }


    return $this->redirectToRoute('admin_services', [], Response::HTTP_SEE_OTHER);
}

#[Route('/gservice/new', name: 'gservice_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $gService = new GService();
    $form = $this->createForm(GServiceType::class, $gService);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($gService);
        $entityManager->flush();

        return $this->redirectToRoute('gservice_index');
    }

    return $this->render('g_service/new.html.twig', [
        'g_service' => $gService,
        'form' => $form->createView(),
    ]);
}
#[Route('/gservice/new', name: 'gservice_new', methods: ['GET', 'POST'])]
public function create(Request $request, EntityManagerInterface $entityManager): Response
{
    $gService = new GService();
    $form = $this->createForm(GServiceType::class, $gService);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($gService);
        $entityManager->flush();

        $this->addFlash('success', 'Service ajouté avec succès.');
        return $this->redirectToRoute('gservice_index'); // ou une autre route selon ton app
    }

    return $this->render('user/service/create.html.twig', [
        'form' => $form->createView(),
    ]);
}




}