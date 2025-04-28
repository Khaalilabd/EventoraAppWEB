<?php

namespace App\Controller\Users;

use App\Entity\Favoris;
use App\Entity\Membre;
use App\Entity\Pack;
use App\Repository\FavorisRepository;
use App\Repository\PackRepository;
use App\Repository\TypepackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/user')]
class PacksController extends AbstractController
{
    #[Route('', name: 'user_packs', methods: ['GET'])]
    public function index(Request $request, PackRepository $packRepository, FavorisRepository $favorisRepository, TypepackRepository $typepackRepository, LoggerInterface $logger): Response
    {
        // Fetch all typepacks for the filter dropdown
        $typepacks = $typepackRepository->findAll();

        // Get filter parameters from query
        $type = $request->query->get('type', '');
        $location = $request->query->get('location', '');
        $minPriceRaw = $request->query->get('minPrice', '');
        $maxPriceRaw = $request->query->get('maxPrice', '');

        // Convert price filters to float or null, treat 0 as null to disable filter
        $minPrice = $minPriceRaw !== '' ? (float) $minPriceRaw : null;
        $maxPrice = $maxPriceRaw !== '' ? (float) $maxPriceRaw : null;
        $minPrice = ($minPrice === 0.0) ? null : $minPrice;
        $maxPrice = ($maxPrice === 0.0) ? null : $maxPrice;

        // Log filter parameters
        $logger->debug('Filter parameters', [
            'type' => $type,
            'location' => $location,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);

        // Fetch filtered packs for all users (admins and non-admins)
        $result = $packRepository->findFilteredForAll(
            page: 1,
            limit: 50,
            sort: 'nomPack',
            order: 'ASC',
            type: $type,
            location: $location,
            minPrice: $minPrice,
            maxPrice: $maxPrice
        );

        $packs = $result['packs']; // Extract the 'packs' array

        // Log number of packs retrieved
        $logger->debug('Packs retrieved', ['count' => count($packs)]);

        // Initialize variables for favorites and recommendations
        $favoritedPackIds = [];
        $recommendedPacks = [];

        // If user is authenticated and not an admin, fetch favorites and recommendations
        /** @var Membre|null $user */
        $user = $this->getUser();
        if ($user instanceof Membre && !$this->isGranted('ROLE_ADMIN')) {
            // Fetch user's favorite packs
            $favorites = $favorisRepository->findBy(['membre' => $user]);
            $favoritedPackIds = array_map(fn($favorite) => $favorite->getPack()->getId(), $favorites);

            // Fetch recommended packs based on favorites
            $recommendedPacks = $packRepository->findSimilarPacks($favorites, 3);
        }

        return $this->render('user/packs/index.html.twig', [
            'packs' => $packs,
            'favoritedPackIds' => $favoritedPackIds,
            'recommendedPacks' => $recommendedPacks,
            'typepacks' => $typepacks,
            'type' => $type,
            'location' => $location,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }

    #[Route('/packs/{id}', name: 'user_pack_details', requirements: ['id' => '\d+'])]
    public function details(int $id, PackRepository $packRepository, FavorisRepository $favorisRepository): Response
    {
        $pack = $packRepository->find($id);

        if (!$pack) {
            throw $this->createNotFoundException('Pack not found');
        }

        // Check if the pack is favorited by the current user
        $isFavorited = false;
        if ($this->getUser() instanceof Membre) {
            $isFavorited = $favorisRepository->findOneByPackAndMembre($pack, $this->getUser()) !== null;
        }

        return $this->render('user/packs/pack_details.html.twig', [
            'pack' => $pack,
            'isFavorited' => $isFavorited,
        ]);
    }

    #[Route('/packs/favorite/{id}', name: 'user_toggle_favorite', methods: ['POST'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function toggleFavorite(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Validate CSRF token
        $submittedToken = $request->request->get('token');
        if (!$this->isCsrfTokenValid('toggle-favorite', $submittedToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        // Get the current user (MEMBRE)
        /** @var Membre $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Find the pack
        $pack = $entityManager->getRepository(Pack::class)->find($id);
        if (!$pack) {
            return new JsonResponse(['error' => 'Pack not found'], 404);
        }

        // Check if the pack is already favorited
        $favoris = $entityManager->getRepository(Favoris::class)->findOneBy([
            'pack' => $pack,
            'membre' => $user,
        ]);

        if ($favoris) {
            // Remove from favorites
            $entityManager->remove($favoris);
            $entityManager->flush();
            return new JsonResponse(['isFavorited' => false]);
        } else {
            // Add to favorites
            $favoris = new Favoris();
            $favoris->setPack($pack);
            $favoris->setMembre($user);
            $entityManager->persist($favoris);
            $entityManager->flush();
            return new JsonResponse(['isFavorited' => true]);
        }
    }

    #[Route('/favorites', name: 'user_favorites')]
    #[IsGranted('ROLE_MEMBRE')]
    public function favoritesList(FavorisRepository $favorisRepository): Response
    {
        /** @var Membre $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        $favorites = $favorisRepository->findBy(['membre' => $user]);

        return $this->render('user/packs/favorites.html.twig', [
            'favorites' => $favorites,
        ]);
    }
}