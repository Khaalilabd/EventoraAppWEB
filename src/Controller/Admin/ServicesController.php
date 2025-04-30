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
    public function index(GServiceRepository $GServiceRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        // Récupérer les paramètres de la requête pour la pagination, le tri et le filtrage
        $page = $request->query->getInt('page', 1);
        $limit = 4; 
        $searchTerm = $request->query->get('search', '');
        $sortBy = $request->query->get('sort_by', 'id'); // Par défaut, trier par ID
        $sortOrder = $request->query->get('sort_order', 'asc'); // Par défaut, ordre croissant
    
        // Créer une requête avec QueryBuilder
        $queryBuilder = $GServiceRepository->createQueryBuilder('s')
            ->leftJoin('s.sponsor', 'sp') // Joindre la relation avec le sponsor (partenaire)
            ->orderBy("s.$sortBy", $sortOrder);
    
        // Ajouter un filtre de recherche sur certains champs
        if ($searchTerm) {
            $queryBuilder->andWhere('s.titre LIKE :search OR s.description LIKE :search OR s.location LIKE :search OR s.type_service LIKE :search OR sp.nom_partenaire LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
    
        // Calculer le nombre total de services pour la pagination
        $totalServices = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalServices / $limit);
    
        // Récupérer les services pour la page actuelle
        $services = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    
        return $this->render('admin/service/index.html.twig', [
            'GServices' => $services, // Liste des services pour la page actuelle
            'current_page' => $page,
            'total_pages' => $totalPages,
            'searchTerm' => $searchTerm,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
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


    #[Route('/{id}', name: 'admin_service_show', methods: ['GET'])]
    public function show(GService $GService): Response
    {
        return $this->render('admin/service/show.html.twig', [
            'GService' => $GService,
        ]);
    } 


}