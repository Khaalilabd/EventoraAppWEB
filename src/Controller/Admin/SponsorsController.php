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
    #[Route('/', name: 'admin_sponsors' , methods:['Get'])]
    public function index(SponsorRepository $SponsorRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $Sponsor = $SponsorRepository->findAll();
        return $this->render('admin/sponsors/index.html.twig', [
            'Sponsor' => $Sponsor,
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
