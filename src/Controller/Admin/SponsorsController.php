<?php


namespace App\Controller\Admin;
use App\Entity\Sponsor;
use App\Form\SponsorsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

#[Route("/admin/sponsors")]
final class SponsorsController extends AbstractController
{
    #[Route('/', name: 'admin_sponsors', methods: ['GET'])]
    public function index(SponsorRepository $SponsorRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        $page = $request->query->getInt('page', 1);
        $limit = 6; // 3 éléments par page, comme dans ServicesController
        $searchTerm = $request->query->get('search', '');
        $sortBy = $request->query->get('sort_by', 'id_partenaire'); // Par défaut, trier par ID
        $sortOrder = $request->query->get('sort_order', 'asc'); // Par défaut, ordre croissant
    
        // Créer une requête avec QueryBuilder
        $queryBuilder = $SponsorRepository->createQueryBuilder('s')
            ->orderBy("s.$sortBy", $sortOrder);
    
        if ($searchTerm) {
            $queryBuilder->andWhere('s.nom_partenaire LIKE :search OR s.email_partenaire LIKE :search OR s.telephone_partenaire LIKE :search OR s.adresse_partenaire LIKE :search OR s.site_web LIKE :search OR s.type_partenaire LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
    
        $totalSponsors = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalSponsors / $limit);
    
        $sponsors = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    
        return $this->render('admin/sponsors/index.html.twig', [
            'Sponsor' => $sponsors, // Liste des sponsors pour la page actuelle
            'current_page' => $page,
            'total_pages' => $totalPages,
            'searchTerm' => $searchTerm,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);
    }
    #[Route('/{id_partenaire}/edit', name: 'admin_sponsors_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sponsor $Sponsor, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(SponsorsType::class, data: $Sponsor);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('admin_sponsors', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('admin/sponsors/edit.html.twig', [
            'Sponsor' => $Sponsor,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id_partenaire}/delete', name: 'admin_sponsors_delete', methods: ['POST'])]
public function delete(Request $request, Sponsor $Sponsor, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');
    if ($this->isCsrfTokenValid('delete' . $Sponsor->getIdPartenaire(), $request->request->get('_token'))) {
        $entityManager->remove(object: $Sponsor);
        $entityManager->flush();
        $this->addFlash('success', message: 'partenaire supprimée avec succès.');
    }else {
        $this->addFlash('error', 'Token CSRF invalide.');
    }


    return $this->redirectToRoute('admin_sponsors', [], Response::HTTP_SEE_OTHER);
}

#[Route("/admin/sponsor", name:"admin_sponsors_create", methods:['GET' , 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer un nouveau service
        $Sponsor = new Sponsor();

        // Créer et gérer le formulaire
        $form = $this->createForm(SponsorsType::class, $Sponsor);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, on persiste l'entité
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist(object: $Sponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Partenaire ajouté avec succès.');

            // Rediriger vers la page des services ou une autre route
            return $this->redirectToRoute('admin_sponsors');
        }

        // Passer le formulaire au template, ainsi que l'entité GService si nécessaire
        return $this->render('admin/sponsors/create.html.twig', [
            'form' => $form->createView(),
            'Sponsor' => $Sponsor,  // Assurez-vous que la variable GService est passée au template
        ]);
    }
    #[Route('/{id}', name: 'admin_sponsors_show', methods: ['GET'])]
    public function show(Sponsor $Sponsor): Response
    {
        return $this->render('admin/sponsors/show.html.twig', [
            'Sponsor' => $Sponsor,
        ]);
    } 

}
