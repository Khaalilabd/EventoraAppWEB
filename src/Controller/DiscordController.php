<?php 
namespace App\Controller; // Changement ici

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use GuzzleHttp\Client;

class DiscordController extends AbstractController
{
    #[Route('/discord/login', name: 'discord_login')]
    public function login(): Response
    {
        $discordUrl = 'https://discord.com/api/oauth2/authorize?client_id=' . $_ENV['DISCORD_CLIENT_ID'] . '&redirect_uri=' . urlencode($_ENV['DISCORD_REDIRECT_URI']) . '&response_type=code&scope=identify';
        return $this->redirect($discordUrl);
    }

    #[Route('/discord/callback', name: 'discord_callback')]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return $this->redirectToRoute('app_auth');
        }

        $client = new Client();
        try {
            $response = $client->post('https://discord.com/api/oauth2/token', [
                'form_params' => [
                    'client_id' => $_ENV['DISCORD_CLIENT_ID'],
                    'client_secret' => $_ENV['DISCORD_CLIENT_SECRET'],
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $_ENV['DISCORD_REDIRECT_URI'],
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $accessToken = $data['access_token'];

            $userResponse = $client->get('https://discord.com/api/users/@me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $user = json_decode($userResponse->getBody(), true);

            return $this->redirectToRoute('app_home');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la connexion avec Discord : ' . $e->getMessage());
            return $this->redirectToRoute('app_auth');
        }
    }

    #[Route('/api/send-discord-message', name: 'send_discord_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $messageContent = $data['message'] ?? null;

        if (!$messageContent) {
            return $this->json(['error' => 'Message requis'], 400);
        }

        $discord = new Discord([
            'token' => $_ENV['DISCORD_BOT_TOKEN'],
        ]);

        $discord->on('ready', function (Discord $discord) use ($messageContent) {
            $channel = $discord->getChannel($_ENV['DISCORD_CHANNEL_ID']);
            if ($channel) {
                $channel->sendMessage($messageContent)->done(function () use ($discord) {
                    $discord->close();
                });
            }
        });

        sleep(2);

        return $this->json(['success' => 'Message envoyé']);
    }
}