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
use Knp\Component\Pager\PaginatorInterface;

#[Route("/admin/service")]
class ServicesController extends AbstractController
{
    #[Route('/', name: 'admin_services', methods: ['GET'])]
    public function index(GServiceRepository $GServiceRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Créer une requête pour récupérer tous les services
        $query = $GServiceRepository->createQueryBuilder('s')->getQuery();

        // Paginer les résultats avec 3 éléments par page
        $GServices = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            3 // 3 éléments par page
        );

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


#[Route("/admin/service", name:"admin_service_create", methods:['GET' , 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer un nouveau service
        $GService = new GService();

        // Créer et gérer le formulaire
        $form = $this->createForm(GServiceType::class, $GService);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, on persiste l'entité
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($GService);
            $entityManager->flush();

            $this->addFlash('success', 'Service ajouté avec succès.');

            // Rediriger vers la page des services ou une autre route
            return $this->redirectToRoute('admin_services');
        }

        // Passer le formulaire au template, ainsi que l'entité GService si nécessaire
        return $this->render('admin/service/create.html.twig', [
            'form' => $form->createView(),
            'GService' => $GService,  // Assurez-vous que la variable GService est passée au template
        ]);
    }


  


}