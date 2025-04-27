<?php

namespace App\Service;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class GoogleCalendarService
{
    private $credentialsPath;
    private $redirectUri;
    private $logger;
    private $requestStack;
    private $translator;

    public function __construct(
        string $credentialsPath,
        string $redirectUri,
        LoggerInterface $logger,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
        $this->credentialsPath = $credentialsPath;
        $this->redirectUri = $redirectUri;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    private function getSession()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            $this->logger->warning('No request available to access session.');
            throw new \RuntimeException('No request available to access session.');
        }
        return $request->getSession();
    }

    public function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Eventora Calendar Integration');
        $client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
        $client->setAuthConfig($this->credentialsPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setRedirectUri($this->redirectUri);

        try {
            $session = $this->getSession();
            $accessToken = $session->get('google_access_token');
            if ($accessToken) {
                $client->setAccessToken($accessToken);
                if ($client->isAccessTokenExpired()) {
                    $refreshToken = $client->getRefreshToken();
                    if ($refreshToken) {
                        $client->fetchAccessTokenWithRefreshToken($refreshToken);
                        $session->set('google_access_token', $client->getAccessToken());
                    } else {
                        $session->remove('google_access_token');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error accessing session for Google Client: ' . $e->getMessage());
        }

        return $client;
    }

    public function getAuthUrl(): string
    {
        return $this->getClient()->createAuthUrl();
    }

    public function handleCallback(string $code): bool
    {
        try {
            $client = $this->getClient();
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);
            if (isset($accessToken['error'])) {
                $this->logger->error('Google Calendar API error: ' . $accessToken['error']);
                return false;
            }
            $this->getSession()->set('google_access_token', $accessToken);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error in Google Calendar callback: ' . $e->getMessage());
            return false;
        }
    }

    public function addEvent($reservation, string $type): ?string
    {
        try {
            $client = $this->getClient();
            if (!$client->getAccessToken()) {
                $this->logger->warning('No Google access token available');
                return null;
            }

            $calendarService = new Google_Service_Calendar($client);
            $calendarId = 'primary';

            $title = $type === 'pack'
                ? $this->translator->trans('event_title_pack', ['%pack%' => $reservation->getPack() ? $reservation->getPack()->getNomPack() : 'N/A'])
                : $this->translator->trans('event_title_personnalise');
            $description = $reservation->getDescription() ?: $this->translator->trans('no_description');
            $date = $reservation->getDate();
            $startDateTime = $date->format('Y-m-d\TH:i:sP');
            $endDateTime = (clone $date)->modify('+2 hours')->format('Y-m-d\TH:i:sP');

            $event = new Google_Service_Calendar_Event([
                'summary' => $title,
                'description' => $description,
                'start' => [
                    'dateTime' => $startDateTime,
                    'timeZone' => 'Africa/Tunis',
                ],
                'end' => [
                    'dateTime' => $endDateTime,
                    'timeZone' => 'Africa/Tunis',
                ],
                'attendees' => [
                    ['email' => $reservation->getEmail()],
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60],
                        ['method' => 'popup', 'minutes' => 10],
                    ],
                ],
            ]);

            $event = $calendarService->events->insert($calendarId, $event);
            $this->logger->info('Google Calendar event created: ' . $event->getId());
            return $event->getHtmlLink();
        } catch (\Exception $e) {
            $this->logger->error('Error creating Google Calendar event: ' . $e->getMessage());
            return null;
        }
    }
}