<?php

namespace App\Controller\Users;

use App\Entity\GService;
use App\Entity\Pack;
use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use App\Repository\GServiceRepository;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/user/cart')]
class CartController extends AbstractController
{
    private LoggerInterface $logger;
    private GServiceRepository $serviceRepository;
    private PackRepository $packRepository;

    public function __construct(
        LoggerInterface $logger,
        GServiceRepository $serviceRepository,
        PackRepository $packRepository
    ) {
        $this->logger = $logger;
        $this->serviceRepository = $serviceRepository;
        $this->packRepository = $packRepository;
    }

    #[Route('/', name: 'app_user_cart')]
    public function index(SessionInterface $session): Response
    {
        // Récupérer les données du panier depuis la session
        $cart = $session->get('cart', [
            'type' => null,
            'items' => [],
            'totalPrice' => 0,
            'reservation' => null
        ]);

        // Récupérer les détails des items (services ou pack) 
        $itemsDetails = [];
        $totalPrice = 0;

        if ($cart['type'] === 'personnalise') {
            // Pour une réservation personnalisée, récupérer les détails des services
            foreach ($cart['items'] as $serviceId) {
                $service = $this->serviceRepository->find($serviceId);
                if ($service) {
                    $itemsDetails[] = [
                        'id' => $service->getId(),
                        'title' => $service->getTitre(),
                        'description' => $service->getDescription(),
                        'price' => $service->getPrix(),
                        'image' => $service->getImage(),
                    ];
                    $totalPrice += $service->getPrix();
                }
            }
        } elseif ($cart['type'] === 'pack') {
            // Pour une réservation de pack, récupérer les détails du pack
            if (isset($cart['items']['packId'])) {
                $pack = $this->packRepository->find($cart['items']['packId']);
                if ($pack) {
                    $itemsDetails[] = [
                        'id' => $pack->getId(),
                        'title' => $pack->getNomPack(),
                        'description' => $pack->getDescription(),
                        'price' => $pack->getPrix(),
                        'image' => $pack->getImage(),
                    ];
                    $totalPrice = $pack->getPrix();
                }
            }
        }

        // Mettre à jour le prix total dans la session
        $cart['totalPrice'] = $totalPrice;
        $session->set('cart', $cart);

        return $this->render('user/cart/index.html.twig', [
            'cart' => $cart,
            'items' => $itemsDetails,
            'totalPrice' => $totalPrice,
        ]);
    }

    #[Route('/payment', name: 'app_user_payment')]
    public function payment(SessionInterface $session): Response
    {
        // Vérifier que le panier n'est pas vide
        $cart = $session->get('cart', [
            'type' => null,
            'items' => [],
            'totalPrice' => 0,
            'reservation' => null
        ]);

        if (empty($cart['items'])) {
            $this->addFlash('error', 'Votre panier est vide!');
            return $this->redirectToRoute('app_user_cart');
        }

        return $this->render('user/cart/payment.html.twig', [
            'cart' => $cart,
            'totalPrice' => $cart['totalPrice'],
        ]);
    }

    #[Route('/payment/success', name: 'app_user_payment_success')]
    public function paymentSuccess(SessionInterface $session): Response
    {
        // Simuler un paiement réussi
        // Dans un système réel, vous recevriez une confirmation du processeur de paiement
        
        // Après un paiement réussi, vider le panier
        $session->remove('cart');
        
        $this->addFlash('success', 'Paiement effectué avec succès! Votre réservation a été confirmée.');
        
        // Rediriger vers l'historique des réservations
        return $this->redirectToRoute('app_user_history_reservations');
    }

    #[Route('/payment/cancel', name: 'app_user_payment_cancel')]
    public function paymentCancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé. Votre réservation est toujours en attente dans votre panier.');
        return $this->redirectToRoute('app_user_cart');
    }
} 