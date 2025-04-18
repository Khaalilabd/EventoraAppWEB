<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LanguageController extends AbstractController
{
    #[Route('/change-language/{locale}', name: 'app_change_language')]
    public function changeLanguage(Request $request, string $locale): Response
    {
        // Vérifier que la locale est supportée
        if (!in_array($locale, ['fr', 'en'])) {
            $locale = 'fr';
        }

        // Stocker la locale dans la session
        $request->getSession()->set('_locale', $locale);

        // Récupérer l'URL de la page précédente (referer) pour rediriger
        $referer = $request->headers->get('referer');
        if (!$referer) {
            $referer = $this->generateUrl('app_settings');
        }

        return $this->redirect($referer);
    }
}