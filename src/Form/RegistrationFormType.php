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
// use Symfony\Component\Validator\Constraints\DateTime; // Cette contrainte est rarement nécessaire sur un DateType avec widget single_text

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis.']),
                    new Length(['min' => 2, 'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.']),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est requis.']),
                    new Length(['min' => 2, 'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères.']),
                ],
            ])
            ->add('dateOfBirth', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false, // La validation NotBlank se ferait sur l'entité si nécessaire, mais le FormType a LessThanOrEqual
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Sélectionnez une date (format AAAA-MM-JJ)',
                ],
                'constraints' => [
                    new LessThanOrEqual([
                        'value' => '2007-04-25', // S'assurer que c'est la date correcte pour 18 ans
                        'message' => 'Vous devez avoir au moins 18 ans pour vous inscrire.',
                    ]),
                    // Si la date de naissance est obligatoire, ajouter NotBlank ici ou sur l'entité
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
                // Si le genre est obligatoire, ajouter NotBlank ici ou sur l'entité
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est requis.']),
                    new \Symfony\Component\Validator\Constraints\Email(['message' => 'L\'adresse email "{{ value }}" n\'est pas valide.']), // Utilisez le FQCN ou un use
                ],
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'constraints' => [
                    new NotBlank(['message' => 'Le CIN est requis.']),
                     new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => "Le numéro de CIN doit être composé de 8 chiffres exactement."
                    ]),
                ],
            ])
            ->add('numTel', TextType::class, [
                'label' => 'Numéro de Téléphone',
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone est requis.']),
                     new Regex([
                        'pattern' => '/^[24579][0-9]{7}$/',
                        'message' => "Veuillez entrer un numéro de téléphone tunisien valide (8 chiffres, commençant par 2, 4, 5, 7 ou 9)."
                    ]),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse est requise.']),
                     new Length(['min' => 5, 'minMessage' => 'L\'adresse complète doit contenir au moins {{ limit }} caractères.']),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'required' => false, // S'assurer que ce champ est bien optionnel si required: false est utilisé
                // Si le nom d'utilisateur a des contraintes (min length, caractères autorisés), les ajouter ici ou sur l'entité
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => $options['is_edit'] ? 'Mot de Passe (laisser vide pour ne pas modifier)' : 'Mot de Passe',
                'required' => !$options['is_edit'],
                // Supprimer 'mapped' => false pour mapper directement à l'entité
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank(['message' => 'Le mot de passe est requis.']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])

            ->add('image', FileType::class, [
                'label' => 'Image (facultatif)',
                'required' => false,
                'mapped' => false, // Ne pas mapper directement sur l'entité (gérer l'upload séparément)
                // Si vous voulez valider le type ou la taille du fichier, ajoutez Assert\File ou Assert\Image ici
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
            'is_edit' => false, // Option pour gérer le mode édition
            
            
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }

    // Assurez-vous que la validation des contraintes de l'entité Membre est aussi activée si vous ne mettez pas *toutes*
    // les contraintes dans le FormType. Souvent, on définit les contraintes de base sur l'entité et des contraintes
    // spécifiques au formulaire dans le FormType.
    // Vous pouvez vérifier dans votre configuration (config/packages/validator.yaml) que validation.enable_annotations est true.
}