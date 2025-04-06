<?php

namespace App\Form;

use App\Entity\Membre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez votre nom'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom ne peut pas être vide.']),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Entrez votre prénom'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom ne peut pas être vide.']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Entrez votre email'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'email ne peut pas être vide.']),
                    new Email(['message' => 'L\'email n\'est pas valide.']),
                ],
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'attr' => ['placeholder' => 'Entrez votre CIN'],
                'constraints' => [
                    new NotBlank(['message' => 'Le CIN ne peut pas être vide.']),
                ],
            ])
            ->add('numTel', TextType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => ['placeholder' => 'Entrez votre numéro de téléphone'],
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone ne peut pas être vide.']),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['placeholder' => 'Entrez votre adresse'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse ne peut pas être vide.']),
                ],
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password', 'placeholder' => 'Entrez votre mot de passe'],
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe ne peut pas être vide.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPG, PNG)',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/jpeg,image/png'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
        ]);
    }
}