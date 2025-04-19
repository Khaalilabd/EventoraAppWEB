<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route("/user/chat_ai")]
final class ChatAiController extends AbstractController
{
    #[Route('/', name: 'user_chat_ai', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/chat_ai/index.html.twig', [
            'controller_name' => 'ChatAiController',
        ]);
    }
}
