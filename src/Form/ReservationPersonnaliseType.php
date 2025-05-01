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
        // Get user data from options, or default empty values
        $userData = $options['user_data'] ?? [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'numtel' => ''
        ];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'last_name',
                'required' => true,
                'data' => $userData['nom'], // Pre-fill with user's last name
                'constraints' => [
                    new NotBlank(['message' => 'Last name is required.']),
                ],
                'translation_domain' => 'messages',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'first_name',
                'required' => true,
                'data' => $userData['prenom'], // Pre-fill with user's first name
                'constraints' => [
                    new NotBlank(['message' => 'First name is required.']),
                ],
                'translation_domain' => 'messages',
            ])
            ->add('email', EmailType::class, [
                'label' => 'email',
                'required' => true,
                'data' => $userData['email'], // Pre-fill with user's email
                'constraints' => [
                    new NotBlank(['message' => 'Email is required.']),
                    new Email(['message' => 'Email is not valid.']),
                ],
                'translation_domain' => 'messages',
            ])
            ->add('numtel', TelType::class, [
                'label' => 'phone_number',
                'required' => true,
                'data' => $userData['numtel'], // Pre-fill with user's phone number (without +216)
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required.']),
                    new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Phone number must be exactly 8 digits.'
                    ]),
                ],
                'translation_domain' => 'messages',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'event_description',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Description is required.']),
                ],
                'translation_domain' => 'messages',
            ])
            ->add('date', DateType::class, [
                'label' => 'event_date',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'flatpickr'],
                'translation_domain' => 'messages',
            ])
            ->add('services', EntityType::class, [
                'label' => 'services',
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
                        'minMessage' => 'You must select at least one service.'
                    ]),
                ],
                'translation_domain' => 'messages',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationpersonnalise::class,
            'user_data' => null, // Option to pass user data
            'translation_domain' => 'messages',
        ]);

        $resolver->setAllowedTypes('user_data', ['array', 'null']);
    }
}