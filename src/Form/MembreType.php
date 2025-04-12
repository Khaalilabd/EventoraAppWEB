<?php

namespace App\Form;

use App\Entity\Membre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MembreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis.']),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est requis.']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est requis.']),
                ],
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'constraints' => [
                    new NotBlank(['message' => 'Le CIN est requis.']),
                ],
            ])
            ->add('numTel', TextType::class, [
                'label' => 'Numéro de Téléphone',
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone est requis.']),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse est requise.']),
                ],
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => $options['is_edit'] ? 'Mot de Passe (laisser vide pour ne pas modifier)' : 'Mot de Passe',
                'required' => !$options['is_edit'], // Obligatoire pour l'ajout, facultatif pour la modification
                'mapped' => false, // On ne mappe pas directement ce champ à l'entité, car on va le hacher
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank(['message' => 'Le mot de passe est requis.']),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Membre' => 'MEMBRE',
                    'Admin' => 'ADMIN',
                    'Agent' => 'AGENT',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le rôle est requis.']),
                ],
            ])
            ->add('isConfirmed', CheckboxType::class, [
                'label' => 'Compte Confirmé',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
            'is_edit' => false, // Option pour différencier l'ajout et la modification
        ]);
    }
}