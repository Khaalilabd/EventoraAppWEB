<?php

namespace App\Controller\Users;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use App\Repository\GServiceRepository;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Form\CartItemType;
use Symfony\Component\Form\FormFactoryInterface;

#[Route('/panier')]
class PanierController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_panier_index')]
    public function index(SessionInterface $session, GServiceRepository $gServiceRepository, FormFactoryInterface $formFactory): Response
    {
        $cartItems = $session->get('cartItems', []);
        $forms = [];

        foreach ($cartItems as &$item) {
            if (!isset($item['quantity'])) {
                $item['quantity'] = 1;
            }
            // Create a form for each cart item
            $form = $formFactory->create(CartItemType::class, [
                'id' => $item['id'],
                'quantity' => $item['quantity'],
            ], [
                'action' => $this->generateUrl('app_panier_update'),
                'method' => 'POST',
            ]);
            $forms[$item['id']] = $form->createView();
        }
        unset($item);

        $total = 0;
        foreach ($cartItems as $item) {
            $price = floatval(preg_replace('/[^0-9,.]/', '', $item['price']));
            $total += $price * ($item['quantity'] ?? 1);
        }

        $session->set('cartItems', $cartItems);

        return $this->render('user/panier/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => number_format($total, 2, ',', ' ') . ' dt',
            'forms' => $forms,
        ]);
    }

    #[Route('/add', name: 'app_panier_add', methods: ['POST'])]
    public function add(Request $request, SessionInterface $session, GServiceRepository $gServiceRepository, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            $this->logger->error('Requête non AJAX pour app_panier_add', ['remote_ip' => $request->getClientIp()]);
            return new JsonResponse(['success' => false, 'message' => 'Requête non autorisée'], 403);
        }

        $csrfToken = $request->headers->get('X-CSRF-Token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('panier', $csrfToken))) {
            $this->logger->error('Token CSRF invalide pour app_panier_add');
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['cartItems']) || !is_array($data['cartItems'])) {
            $this->logger->error('Données invalides reçues dans app_panier_add', ['data' => $data]);
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
        }

        $newItems = $data['cartItems'];
        $cartItems = $session->get('cartItems', []);

        foreach ($newItems as $newItem) {
            if (!isset($newItem['id']) || !is_numeric($newItem['id'])) {
                $this->logger->error('ID de service invalide', ['newItem' => $newItem]);
                return new JsonResponse(['success' => false, 'message' => 'ID de service invalide'], 400);
            }

            $service = $gServiceRepository->find($newItem['id']);
            if (!$service) {
                $this->logger->error('Service introuvable', ['service_id' => $newItem['id']]);
                return new JsonResponse(['success' => false, 'message' => 'Service introuvable'], 404);
            }

            $existingItemKey = null;
            foreach ($cartItems as $key => $item) {
                if ($item['id'] == $newItem['id']) {
                    $existingItemKey = $key;
                    break;
                }
            }

            if ($existingItemKey !== null) {
                $cartItems[$existingItemKey]['quantity'] = isset($newItem['quantity']) ? (int)$newItem['quantity'] : ($cartItems[$existingItemKey]['quantity'] ?? 1);
            } else {
                $cartItems[] = [
                    'id' => $newItem['id'],
                    'title' => $service->getTitre(),
                    'price' => $service->getPrix() . ' dt',
                    'location' => $service->getLocation(),
                    'type' => $service->getTypeService(),
                    'quantity' => isset($newItem['quantity']) ? (int)$newItem['quantity'] : 1,
                    'image' => $service->getImage() ?? 'https://via.placeholder.com/400x200',
                ];
            }
        }

        $session->set('cartItems', $cartItems);
        $this->logger->info('Panier mis à jour', ['cartItems' => $cartItems]);

        return new JsonResponse(['success' => true, 'message' => 'Panier mis à jour']);
    }

    #[Route('/update', name: 'app_panier_update', methods: ['POST'])]
    public function update(Request $request, SessionInterface $session, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('panier', $csrfToken))) {
            $this->logger->error('Token CSRF invalide pour app_panier_update');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('app_panier_index');
        }

        $itemId = null;
        $quantity = null;

        if ($request->isXmlHttpRequest()) {
            $data = json_decode($request->getContent(), true);
            $itemId = $data['id'] ?? null;
            $quantity = $data['quantity'] ?? null;
        } else {
            $itemId = $request->request->get('cart_item[id]');
            $quantity = $request->request->get('cart_item[quantity]');
        }

        if ($itemId === null || $quantity === null || $quantity < 1) {
            $this->logger->error('Données invalides pour app_panier_update', ['itemId' => $itemId, 'quantity' => $quantity]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
            }
            $this->addFlash('error', 'Données invalides');
            return $this->redirectToRoute('app_panier_index');
        }

        $cartItems = $session->get('cartItems', []);
        $updated = false;
        foreach ($cartItems as &$item) {
            if ($item['id'] == $itemId) {
                $item['quantity'] = (int)$quantity;
                $updated = true;
                break;
            }
        }
        unset($item);

        if (!$updated) {
            $this->logger->error('Article introuvable dans le panier', ['item_id' => $itemId]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Article introuvable dans le panier'], 404);
            }
            $this->addFlash('error', 'Article introuvable dans le panier');
            return $this->redirectToRoute('app_panier_index');
        }

        $session->set('cartItems', $cartItems);
        $this->logger->info('Quantité mise à jour', ['item_id' => $itemId, 'quantity' => $quantity]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true, 'message' => 'Quantité mise à jour']);
        }

        $this->addFlash('success', 'Quantité mise à jour');
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/remove/{id}', name: 'app_panier_remove', methods: ['POST'])]
    public function remove(Request $request, int $id, SessionInterface $session, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $csrfToken = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_csrf_token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('panier', $csrfToken))) {
            $this->logger->error('Token CSRF invalide pour app_panier_remove');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('app_panier_index');
        }

        $cartItems = $session->get('cartItems', []);
        $cartItems = array_filter($cartItems, fn($item) => $item['id'] != $id);
        $cartItems = array_values($cartItems);

        $session->set('cartItems', $cartItems);
        $this->logger->info('Article supprimé du panier', ['item_id' => $id]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true, 'message' => 'Article supprimé du panier']);
        }

        $this->addFlash('success', 'Article supprimé du panier');
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/get', name: 'app_panier_get', methods: ['GET'])]
    public function get(SessionInterface $session): JsonResponse
    {
        $cartItems = $session->get('cartItems', []);
        foreach ($cartItems as &$item) {
            if (!isset($item['quantity'])) {
                $item['quantity'] = 1;
            }
        }
        unset($item);

        $session->set('cartItems', $cartItems);
        return new JsonResponse(['cartItems' => $cartItems]);
    }
}