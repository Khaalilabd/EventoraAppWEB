<?php

namespace App\Command;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Intents;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'discord:bot',
    description: 'Démarre le bot Discord pour écouter les événements vocaux et textuels',
)]
class DiscordBotCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Démarrage du bot Discord...');

        $discord = new Discord([
            'token' => $_ENV['DISCORD_BOT_TOKEN'],
            'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS | Intents::GUILD_VOICE_STATES | Intents::GUILD_MESSAGES,
        ]);

        $discord->on('init', function (Discord $discord) use ($io) {
            $io->success('Bot Discord initialisé !');

            // Attendre un délai pour s'assurer que les canaux sont chargés
            $discord->getLoop()->addTimer(5, function () use ($discord, $io) {
                // Vérifier si $discord->channels est itérable
                if (!empty($discord->channels) && (is_array($discord->channels) || $discord->channels instanceof \Traversable)) {
                    foreach ($discord->channels as $channel) {
                        $io->info("Canal trouvé : {$channel->name} (ID: {$channel->id})");
                    }
                } else {
                    $io->warning("Aucun canal trouvé ou \$discord->channels est vide.");
                }
            });

            // Ajouter un log pour confirmer que l'événement voiceStateUpdate est enregistré
            $discord->on('voiceStateUpdate', function (VoiceStateUpdate $state, Discord $discord) use ($io) {
                $io->info('Événement voiceStateUpdate déclenché !');

                $member = $state->member;
                $newChannel = $state->channel;
                $oldChannel = $state->old_channel;

                $io->info('Membre : ' . ($member->username ?? 'Inconnu'));
                $io->info('Nouveau canal : ' . ($newChannel ? $newChannel->name : 'Aucun'));
                $io->info('Ancien canal : ' . ($oldChannel ? $oldChannel->name : 'Aucun'));

                $textChannel = $discord->getChannel($_ENV['DISCORD_CHANNEL_ID']);
                if (!$textChannel) {
                    $io->error('Canal #eventora introuvable ! Vérifiez DISCORD_CHANNEL_ID.');
                    return;
                }

                if ($newChannel && !$oldChannel) {
                    $io->info("{$member->username} a rejoint le salon vocal {$newChannel->name}");

                    if ($newChannel->name === 'EventoraAPPWEB') {
                        $io->info("Envoi du message de bienvenue dans #eventora...");
                        $textChannel->sendMessage("🎉 Bienvenue {$member->username} dans le salon vocal **EventoraAPPWEB** ! Parle-nous de ton événement !")
                            ->done(function () use ($io) {
                                $io->info("Message de bienvenue envoyé avec succès !");
                            })
                            ->otherwise(function ($error) use ($io) {
                                $io->error("Erreur lors de l’envoi du message : " . $error->getMessage());
                            });
                    }
                }

                if (!$newChannel && $oldChannel) {
                    $io->info("{$member->username} a quitté le salon vocal {$oldChannel->name}");

                    if ($oldChannel->name === 'EventoraAPPWEB') {
                        $io->info("Envoi du message de départ dans #eventora...");
                        $textChannel->sendMessage("👋 {$member->username} a quitté le salon vocal **EventoraAPPWEB**. À bientôt !")
                            ->done(function () use ($io) {
                                $io->info("Message de départ envoyé avec succès !");
                            })
                            ->otherwise(function ($error) use ($io) {
                                $io->error("Erreur lors de l’envoi du message : " . $error->getMessage());
                            });
                    }
                }
            });

            $io->info("Événement voiceStateUpdate enregistré !");

            $discord->on('message', function ($message, Discord $discord) use ($io) {
                if ($message->author->bot) {
                    return;
                }

                if ($message->channel_id === $_ENV['DISCORD_CHANNEL_ID']) {
                    $io->info("Nouveau message détecté !");
                    $io->info("Auteur : {$message->author->username}");
                    $io->info("Canal ID : {$message->channel_id}");
                    $io->info("Contenu du message : {$message->content}");

                    if (strtolower($message->content) === '!salut') {
                        $io->info("Commande !salut détectée, envoi de la réponse...");
                        $message->channel->sendMessage("Salut {$message->author->username} ! Comment ça va ?")
                            ->done(function () use ($io) {
                                $io->info("Réponse à !salut envoyée avec succès !");
                            })
                            ->otherwise(function ($error) use ($io) {
                                $io->error("Erreur lors de l’envoi de la réponse : " . $error->getMessage());
                            });
                    }
                }
            });
        });

        $discord->on('error', function ($error) use ($io) {
            $io->error('Erreur Discord : ' . $error->getMessage());
        });

        $discord->run();

        return Command::SUCCESS;
    }
}