<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la réclamation',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre ne peut pas être vide.']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'Le titre ne peut pas dépasser 255 caractères.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => '5'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description ne peut pas être vide.']),
                ],
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
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un type de réclamation.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}