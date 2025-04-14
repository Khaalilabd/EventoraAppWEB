<?php

namespace App\Form;

use App\Entity\Pack;
use App\Entity\GService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPack', TextType::class, [
                'label' => 'Nom du pack',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'required' => true,
                'scale' => 2,
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Lieu',
                'choices' => [
                    'Hôtel' => 'HOTEL',
                    'Maison d\'hôte' => 'MAISON_D_HOTE',
                    'Espace vert' => 'ESPACE_VERT',
                    'Salle de fête' => 'SALLE_DE_FETE',
                    'Autre' => 'AUTRE',
                ],
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de pack',
                'choices' => [
                    'Anniversaire' => 'Anniversaire',
                    'Mariage' => 'Mariage',
                    'Conférence' => 'Conférence',
                    'Soirée' => 'Soirée',
                    // Ajoutez d'autres types selon vos besoins
                ],
                'required' => true,
                'placeholder' => 'Sélectionnez un type', // Optionnel
            ])
            ->add('nbrGuests', NumberType::class, [
                'label' => 'Nombre d\'invités',
                'required' => true,
            ])
            ->add('image_path', FileType::class, [
                'label' => 'Image du pack',
                'mapped' => false,
                'required' => false,
            ])
            ->add('services', EntityType::class, [
                'class' => GService::class,
                'choice_label' => 'titre',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pack::class,
        ]);
    }
}