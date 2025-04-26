<?php

namespace App\Form;

use App\Entity\Membre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\DateTime;

class RegistrationFormType extends AbstractType
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
            ->add('dateOfBirth', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Sélectionnez une date (format AAAA-MM-JJ)',
                ],
                'constraints' => [
                    new LessThanOrEqual([
                        'value' => '2007-04-25',
                        'message' => 'Vous devez avoir au moins 18 ans pour vous inscrire.',
                    ]),
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'required' => false,
                'choices' => [
                    'Homme' => 'Homme',
                    'Femme' => 'Femme',
                ],
                'placeholder' => 'Sélectionnez votre genre (facultatif)',
                'attr' => [
                    'class' => 'form-select',
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
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'required' => false,
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => $options['is_edit'] ? 'Mot de Passe (laisser vide pour ne pas modifier)' : 'Mot de Passe',
                'required' => !$options['is_edit'],
                'mapped' => false,
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank(['message' => 'Le mot de passe est requis.']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]).{8,}$/',
                        'message' => 'Le mot de passe doit contenir au moins une lettre minuscule, une majuscule, un chiffre et un caractère spécial.',
                    ]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (facultatif)',
                'required' => false,
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}