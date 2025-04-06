<?php

namespace App\Form;

use App\Entity\GService;
use App\Entity\Sponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('Sponsor', EntityType::class, [
            'label' => 'Partenaire (Sponsor)',
            'class' => Sponsor::class,
            'choice_label' => 'nom_partenaire', // ou un autre champ à afficher
            'placeholder' => 'Sélectionner un sponsor',
        ])
            ->add('titre', TextType::class, [
                'label' => 'Titre du service',
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Lieu',
                'choices' => [
                    'Hotel' => 'hotel',
                    'Maison_d_hote' => 'maison_d_hote',
                    'Espace_vert' => 'espace_vert',
                    'Salle_de_fete' => 'salle_de_fete',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisissez un lieu',
            ])
            ->add('typeService', ChoiceType::class, [
                'label' => 'Type de service',
                'choices' => [
                    'Decoration' => 'decoration',
                    'Lumiere' => 'lumiere',
                    'Sono' => 'sono',
                    'Traiteur' => 'traiteur',
                    'Fleuriste' => 'fleuriste',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisissez un type',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('prix', TextType::class, [
                'label' => 'Prix',
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GService::class,
        ]);
    }
}
