<?php

namespace App\Form;

use App\Entity\Membre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            ->add('dateOfBirth', TextType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Sélectionnez une date (format JJ/MM/AAAA)',
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
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Membre' => 'MEMBRE',
                    'Agent' => 'AGENT',
                    'Admin' => 'ADMIN',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('isConfirmed', CheckboxType::class, [
                'label' => 'Compte confirmé',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);

        // Ajouter un CallbackTransformer pour convertir la chaîne en DateTime et vice versa
        $builder->get('dateOfBirth')
            ->addModelTransformer(new CallbackTransformer(
                function ($dateAsObject) {
                    // Transforme DateTime en chaîne (pour l'affichage)
                    return $dateAsObject instanceof \DateTimeInterface ? $dateAsObject->format('d/m/Y') : '';
                },
                function ($dateAsString) {
                    // Transforme chaîne en DateTime (pour la soumission)
                    return $dateAsString ? \DateTime::createFromFormat('d/m/Y', $dateAsString) : null;
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
            'is_edit' => false,
        ]);
    }
}