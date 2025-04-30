const { Client, GatewayIntentBits, ActionRowBuilder, StringSelectMenuBuilder, StringSelectMenuOptionBuilder } = require('discord.js');
const mysql = require('mysql2/promise');

// Configurer la connexion à la base de données MySQL
const dbConfig = {
    host: 'localhost', // Remplacez par l'adresse de votre serveur MySQL
    user: 'root', // Remplacez par votre utilisateur MySQL
    password: '', // Remplacez par votre mot de passe MySQL
    database: 'eventora' // Nom de votre base de données
};

// Créez un nouveau client Discord avec les intents nécessaires
const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMembers,
        GatewayIntentBits.GuildMessages,
        GatewayIntentBits.MessageContent,
        GatewayIntentBits.GuildVoiceStates
    ]
});

// Lorsque le bot est prêt
client.once('ready', async () => {
    console.log(`[OK] Bot Discord initialisé ! ${client.user.tag} est connecté.`);

    // Tester la connexion à la base de données
    try {
        const connection = await mysql.createConnection(dbConfig);
        console.log('[INFO] Connexion à la base de données MySQL réussie !');
        await connection.end();
    } catch (error) {
        console.error('[ERROR] Erreur lors de la connexion à la base de données MySQL :', error);
    }
});

// Événement voiceStateUpdate pour détecter les changements d'état vocal
client.on('voiceStateUpdate', (oldState, newState) => {
    console.log('[INFO] Événement voiceStateUpdate déclenché !');

    const member = newState.member;
    const newChannel = newState.channel;
    const oldChannel = oldState.channel;

    console.log(`[INFO] Membre : ${member.user.tag}`);
    console.log(`[INFO] Nouveau canal : ${newChannel ? newChannel.name : 'Aucun'}`);
    console.log(`[INFO] Ancien canal : ${oldChannel ? oldChannel.name : 'Aucun'}`);

    // Vérifiez si le membre a rejoint le salon vocal "EventoraAPPWEB"
    if (newChannel && newChannel.name === 'EventoraAPPWEB' && (!oldChannel || oldChannel.name !== 'EventoraAPPWEB')) {
        console.log('[INFO] Envoi du message de bienvenue et du menu dans #eventora...');

        // Trouver le canal textuel #eventora
        const textChannel = newState.guild.channels.cache.find(channel => channel.name === 'eventora' && channel.type === 0); // 0 = canal textuel
        if (textChannel) {
            // Créer le menu déroulant avec les options
            const selectMenu = new StringSelectMenuBuilder()
                .setCustomId('menu-options')
                .setPlaceholder('Choisissez une option...')
                .addOptions(
                    new StringSelectMenuOptionBuilder()
                        .setLabel('Soumettre une réclamation')
                        .setDescription('Pour signaler un problème ou une plainte.')
                        .setValue('reclamation'),
                    new StringSelectMenuOptionBuilder()
                        .setLabel('Soumettre un feedback')
                        .setDescription('Donnez votre avis sur Eventora.')
                        .setValue('feedback'),
                    new StringSelectMenuOptionBuilder()
                        .setLabel('Savoir des informations sur Eventora')
                        .setDescription('En savoir plus sur Eventora.')
                        .setValue('infos'),
                    new StringSelectMenuOptionBuilder()
                        .setLabel('Afficher la liste des packs disponibles')
                        .setDescription('Voir les packs que nous proposons.')
                        .setValue('packs'),
                    new StringSelectMenuOptionBuilder()
                        .setLabel('Passer une réservation')
                        .setDescription('Réserver un pack ou un événement.')
                        .setValue('reservation'),
                    new StringSelectMenuOptionBuilder()
                        .setLabel('Taper un paragraphe bien détaillé')
                        .setDescription('Obtenir une description détaillée.')
                        .setValue('paragraphe')
                );

            // Ajouter le menu à un composant d'action
            const row = new ActionRowBuilder().addComponents(selectMenu);

            // Envoyer le message de bienvenue avec le menu
            textChannel.send({
                content: `🎉 Bienvenue ${member.user.username} dans le salon vocal **EventoraAPPWEB** ! Eventora est une plateforme intuitive qui simplifie l’organisation d’événements : réservez des packs personnalisés, gérez vos réservations, soumettez réclamations et avis, et recevez des notifications en temps réel.\n\nQue souhaitez-vous faire ?`,
                components: [row]
            })
                .then(() => console.log('[INFO] Message de bienvenue et menu envoyés avec succès !'))
                .catch(error => console.error('[ERROR] Erreur lors de l’envoi du message/menu :', error));
        } else {
            console.log('[WARNING] Canal #eventora non trouvé !');
        }
    }
});

// Gérer les sélections dans le menu déroulant
client.on('interactionCreate', async interaction => {
    if (interaction.isStringSelectMenu() && interaction.customId === 'menu-options') {
        const selectedValue = interaction.values[0];

        switch (selectedValue) {
            case 'reclamation':
                await interaction.reply({
                    content: '📝 **Soumettre une réclamation** : Cliquez sur le lien suivant pour soumettre votre réclamation : [Soumettre une réclamation](https://d660-197-17-125-97.ngrok-free.app/reclamation/new)',
                    ephemeral: true
                });
                break;

            case 'feedback':
                await interaction.reply({
                    content: '💬 **Soumettre un feedback** : Partagez votre avis ici : [Soumettre un feedback](https://d660-197-17-125-97.ngrok-free.app/feedback/new)',
                    ephemeral: true
                });
                break;

            case 'infos':
                await interaction.reply({
                    content: 'ℹ️ **Informations sur Eventora** : Eventora est une plateforme intuitive qui simplifie l’organisation d’événements. Consultez et réservez des packs personnalisés, gérez vos réservations via un espace personnel, soumettez réclamations et avis, et recevez des notifications en temps réel. Les organisateurs bénéficient d’un tableau de bord pour gérer services, packs et réclamations.',
                    ephemeral: true
                });
                break;

            case 'packs':
                try {
                    const connection = await mysql.createConnection(dbConfig);
                    const [rows] = await connection.execute('SELECT * FROM pack');

                    if (rows.length === 0) {
                        await interaction.reply({
                            content: '📦 **Aucun pack disponible pour le moment.**',
                            ephemeral: true
                        });
                        await connection.end();
                        return;
                    }

                    let packList = '📦 **Liste des packs disponibles** :\n';
                    rows.forEach(row => {
                        packList += `- **${row.name}** : ${row.price}€ - ${row.description}\n`;
                    });
                    packList += 'Pour réserver un pack, sélectionnez l’option "Passer une réservation".';

                    await interaction.reply({
                        content: packList,
                        ephemeral: true
                    });
                    await connection.end();
                } catch (error) {
                    console.error('[ERROR] Erreur lors de la récupération des packs :', error);
                    await interaction.reply({
                        content: '❌ Erreur lors de la récupération des packs.',
                        ephemeral: true
                    });
                }
                break;

            case 'reservation':
                await interaction.reply({
                    content: '📅 **Passer une réservation** : Réservez votre pack ou événement ici : [Passer une réservation](https://d660-197-17-125-97.ngrok-free.app/reservation/new)',
                    ephemeral: true
                });
                break;

            case 'paragraphe':
                await interaction.reply({
                    content: '📜 **Paragraphe détaillé** : Eventora est une plateforme conçue pour simplifier l’organisation de vos événements, qu’il s’agisse d’anniversaires, de mariages, ou de conférences professionnelles. Nous mettons à votre disposition une gamme de packs adaptés à tous les budgets, allant du Pack Basique pour les petits événements, au Pack Premium qui inclut des services complets comme la location de salle, les décorations, et même un traiteur. Notre équipe est dédiée à faire de votre événement un moment inoubliable, en vous accompagnant à chaque étape, de la planification à la réalisation. Rejoignez notre communauté sur Discord pour poser vos questions et découvrir nos dernières offres !',
                    ephemeral: true
                });
                break;

            default:
                await interaction.reply({
                    content: '❓ Option non reconnue. Veuillez réessayer.',
                    ephemeral: true
                });
        }
    }
});

// Connexion du bot avec votre token
client.login('MTM2NTc3ODc5NzYwNTAzMTk3Ng.GuIkur.0vX6AwLDye26ICFyw2z7Jzhe1pPm5J5iNgKclU');