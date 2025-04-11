<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la réclamation',
                'required' => false, // Désactiver la validation HTML5
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false, // Désactiver la validation HTML5
            ])
            ->add('Type', ChoiceType::class, [
                'label' => 'Type de réclamation',
                'choices' => [
                    'Packs' => Reclamation::TYPE_PACKS,
                    'Service' => Reclamation::TYPE_SERVICE,
                    'Problème Technique' => Reclamation::TYPE_PROBLEME_TECHNIQUE,
                    'Plainte entre un Agent de contrôle' => Reclamation::TYPE_PLAINTE_AGENT,
                    'Autre' => Reclamation::TYPE_AUTRE,
                ],
                'placeholder' => 'Choisissez un type',
                'required' => false, // Désactiver la validation HTML5
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}