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
                'label' => 'Complaint Title',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The title cannot be empty.']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'The title cannot exceed 255 characters.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => '5'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The description cannot be empty.']),
                ],
            ])
            ->add('Type', ChoiceType::class, [
                'label' => 'Complaint Type',
                'choices' => [
                    'Packs' => Reclamation::TYPE_PACKS,
                    'Service' => Reclamation::TYPE_SERVICE,
                    'Problème Technique' => Reclamation::TYPE_PROBLEME_TECHNIQUE,
                    'Plainte entre un Agent de contrôle' => Reclamation::TYPE_PLAINTE_AGENT,
                    'Autre' => Reclamation::TYPE_AUTRE,
                ],
                'placeholder' => 'Choose a type',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a complaint type.']),
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