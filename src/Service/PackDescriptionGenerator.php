<?php

namespace App\Service;

use App\Entity\Pack;

class PackDescriptionGenerator
{
    /**
     * Generates a description for a Pack based on its attributes.
     *
     * @param Pack $pack
     * @return string
     */
    public function generateDescription(Pack $pack): string
    {
        // Define templates by type
        $templates = [
            'Wedding' => [
                'Organisez un mariage inoubliable avec %s, un pack conçu pour %d invités dans %s. À seulement %.2f dt, profitez de services tels que %s pour une journée magique.',
                'Célébrez votre union avec %s, un pack mariage pour %d invités, situé à %s. Pour %.2f dt, inclut %s pour une expérience romantique et personnalisée.',
                '%s est le pack parfait pour votre mariage, accueillant %d invités à %s. Prix : %.2f dt, avec des services comme %s pour un événement mémorable.',
            ],
            'Conference' => [
                'Boostez votre événement professionnel avec %s, un pack pour %d participants à %s. À %.2f dt, inclut %s pour une conférence réussie.',
                '%s offre une solution idéale pour votre conférence, accueillant %d invités à %s. Pour %.2f dt, profitez de %s pour un événement productif.',
                'Planifiez une conférence exceptionnelle avec %s, pour %d participants dans %s. Prix : %.2f dt, avec %s inclus.',
            ],
            'Party' => [
                'Faites la fête avec %s, un pack festif pour %d invités à %s. À %.2f dt, inclut %s pour une soirée inoubliable.',
                '%s est le choix parfait pour votre soirée, accueillant %d invités à %s. Pour %.2f dt, profitez de %s pour une ambiance électrisante.',
                'Organisez une fête mémorable avec %s, pour %d convives à %s. Prix : %.2f dt, avec %s pour un événement vibrant.',
            ],
            'default' => [
                'Découvrez %s, un pack idéal pour %d invités, situé à %s. Prix : %.2f dt, inclut %s pour un événement sur mesure.',
                '%s vous offre une expérience unique pour %d invités à %s. À %.2f dt, profitez de %s pour un moment exceptionnel.',
                'Planifiez votre événement avec %s, conçu pour %d invités à %s. Pour %.2f dt, inclut %s pour une expérience personnalisée.',
            ],
        ];

        // Select template based on type
        $type = $pack->getType() ?? 'default';
        $templateGroup = $templates[$type] ?? $templates['default'];
        $template = $templateGroup[array_rand($templateGroup)];

        // Prepare data
        $nomPack = $pack->getNomPack() ?? 'ce pack';
        $nbrGuests = $pack->getNbrGuests() ?? 0;
        $location = $this->formatLocation($pack->getLocation() ?? 'un lieu unique');
        $prix = $pack->getPrix() ?? 0.00;
        $services = !empty($pack->getServices()) 
            ? implode(', ', array_map(fn($service) => $service->getTitre(), $pack->getServices()))
            : 'aucun service spécifique';

        // Generate description
        return sprintf($template, $nomPack, $nbrGuests, $location, $prix, $services);
    }

    /**
     * Formats location for natural language.
     *
     * @param string $location
     * @return string
     */
    private function formatLocation(string $location): string
    {
        $locationMap = [
            'HOTEL' => 'un hôtel élégant',
            'MAISON_D_HOTE' => 'une maison d\'hôte chaleureuse',
            'ESPACE_VERT' => 'un espace vert paisible',
            'SALLE_DE_FETE' => 'une salle de fête moderne',
            'AUTRE' => 'un lieu unique',
        ];
        return $locationMap[$location] ?? $location;
    }
}