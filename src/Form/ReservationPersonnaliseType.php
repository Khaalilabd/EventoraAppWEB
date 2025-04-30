<?php

namespace App\Form;

use App\Entity\Reservationpersonnalise;
use App\Entity\GService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Count;

class ReservationPersonnaliseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer les données de l'utilisateur depuis les options, ou valeurs par défaut vides
        $userData = $options['user_data'] ?? [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'numtel' => ''
        ];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'data' => $userData['nom'], // Pré-remplir avec le nom de l'utilisateur
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis.']),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'data' => $userData['prenom'], // Pré-remplir avec le prénom de l'utilisateur
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est requis.']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'data' => $userData['email'], // Pré-remplir avec l'email de l'utilisateur
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est requis.']),
                    new Email(['message' => 'L\'email n\'est pas valide.']),
                ],
            ])
            ->add('numtel', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
                'data' => $userData['numtel'], // Pré-remplir avec le numéro de téléphone (sans +216)
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone est requis.']),
                    new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Le numéro de téléphone doit être composé de 8 chiffres.'
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de l\'événement',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La description est requise.']),
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date de l\'événement',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'flatpickr'],
            ])
            ->add('services', EntityType::class, [
                'class' => GService::class,
                'choice_label' => 'titre',
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                              ->orderBy('s.titre', 'ASC');
                },
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'Vous devez sélectionner au moins un service.'
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationpersonnalise::class,
            'user_data' => null, // Option pour passer les données de l'utilisateur
        ]);

        $resolver->setAllowedTypes('user_data', ['array', 'null']);
    }
}