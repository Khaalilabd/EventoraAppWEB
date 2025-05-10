<?php

namespace App\Controller\Users;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PaymentHistory;

class PaymentController extends AbstractController
{
    #[Route('/stripe', name: 'app_stripe')]
    public function index(SessionInterface $session): Response
    {
        // Récupérer les éléments du panier depuis la session
        $cartItems = $session->get('cartItems', []);

        // Vérifier que le panier n'est pas vide
        if (empty($cartItems)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        // Calculer le total du panier (en millimes pour TND : 1 TND = 1000 millimes)
        $totalInMillimes = 0;
        foreach ($cartItems as $item) {
            $priceInMillimes = $item['price_in_millimes'] ?? (floatval(preg_replace('/[^0-9,.]/', '', $item['price'])) * 1000);
            $totalInMillimes += $priceInMillimes * ($item['quantity'] ?? 1);
        }

        // Vérifier que le total est supérieur à 0
        if ($totalInMillimes <= 0) {
            $this->addFlash('error', 'Le montant du panier est invalide.');
            return $this->redirectToRoute('app_cart');
        }

        return $this->render('user/payment/index.html.twig', [
            'stripe_key' => $this->getParameter('stripe_key'),
            'total' => number_format($totalInMillimes / 1000, 2, '.', ''),
        ]);
    }

    #[Route('/stripe/success', name: 'app_stripe_success')]
    public function success(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les informations de réservation avant de vider le panier
        $reservationData = $session->get('reservation_data', null);
        $cartItems = $session->get('cartItems', []);
        
        // Calculer le montant total du panier
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $price = floatval(preg_replace('/[^0-9,.]/', '', $item['price']));
            $totalAmount += $price * ($item['quantity'] ?? 1);
        }
        
        // Créer un enregistrement dans l'historique des paiements
        if ($reservationData && $this->getUser()) {
            $paymentHistory = new PaymentHistory();
            $paymentHistory->setTransactionId('tr_' . uniqid());
            $paymentHistory->setAmount($totalAmount);
            $paymentHistory->setCurrency('TND');
            $paymentHistory->setStatus('completed');
            $paymentHistory->setPaymentMethod('card');
            $paymentHistory->setMembre($this->getUser());
            $paymentHistory->setReservationType($reservationData['type']);
            $paymentHistory->setReservationId($reservationData['id']);
            
            // Enregistrer les détails du paiement (items achetés)
            $paymentHistory->setDetails([
                'items' => $cartItems,
                'reservation' => $reservationData
            ]);
            
            $entityManager->persist($paymentHistory);
            $entityManager->flush();
        }
        
        // Vider le panier après un paiement réussi
        $session->set('cartItems', []);

        // Mais garder les informations de réservation pour affichage
        if ($reservationData) {
            $this->addFlash('success', 'Paiement réussi pour la réservation du ' . $reservationData['date']);
        } else {
            $this->addFlash('success', 'Paiement effectué avec succès!');
        }

        return $this->render('user/payment/success.html.twig', [
            'reservation_data' => $reservationData
        ]);
    }

    #[Route('/stripe/create-payment-intent', name: 'app_stripe_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request, SessionInterface $session): JsonResponse
    {
        // Récupérer les éléments du panier depuis la session
        $cartItems = $session->get('cartItems', []);

        // Vérifier que le panier n'est pas vide
        if (empty($cartItems)) {
            return $this->json(['error' => 'Votre panier est vide.'], Response::HTTP_BAD_REQUEST);
        }

        // Calculer le total du panier (en millimes pour TND : 1 TND = 1000 millimes)
        $totalInMillimes = 0;
        foreach ($cartItems as $item) {
            $priceInMillimes = $item['price_in_millimes'] ?? (floatval(preg_replace('/[^0-9,.]/', '', $item['price'])) * 1000);
            $totalInMillimes += $priceInMillimes * ($item['quantity'] ?? 1);
        }

        // Vérifier que le total est supérieur à 0
        if ($totalInMillimes <= 0) {
            return $this->json(['error' => 'Le montant du panier est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer l'ID du PaymentMethod depuis la requête
        $content = json_decode($request->getContent(), true);
        $paymentMethodId = $content['payment_method_id'] ?? null;

        if (!$paymentMethodId) {
            return $this->json(['error' => 'ID de méthode de paiement manquant.'], Response::HTTP_BAD_REQUEST);
        }

        Stripe::setApiKey($this->getParameter('stripe_secret'));

        try {
            // Créer un PaymentIntent avec le PaymentMethod (montant en millimes pour TND)
            $paymentIntent = PaymentIntent::create([
                'amount' => round($totalInMillimes), // Montant en millimes
                'currency' => 'USD',
                'payment_method_types' => ['card'],
                'payment_method' => $paymentMethodId,
                'description' => 'Paiement pour les articles du panier',
                'confirm' => false,
            ]);

            return $this->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            return $this->json(['error' => 'Erreur de carte : ' . $e->getError()->message], Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\RateLimitException $e) {
            return $this->json(['error' => 'Trop de requêtes, veuillez réessayer plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return $this->json(['error' => 'Requête invalide : ' . $e->getError()->message], Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return $this->json(['error' => 'Erreur d\'authentification Stripe.'], Response::HTTP_UNAUTHORIZED);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            return $this->json(['error' => 'Erreur de connexion au serveur Stripe.'], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return $this->json(['error' => 'Erreur API Stripe : ' . $e->getError()->message], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}