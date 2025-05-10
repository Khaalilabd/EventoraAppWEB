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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

        // Check if we're creating a new entity (no ID) or editing an existing one
        $isNew = !$builder->getData() || !method_exists($builder->getData(), 'getIdReservationPersonalise') || 
                 !$builder->getData()->getIdReservationPersonalise();
        
        // Only set default data for new entities
        $fieldConfig = [
            'nom' => [
                'label' => 'last_name',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Last name is required.']),
                ],
                'translation_domain' => 'messages',
            ],
            'prenom' => [
                'label' => 'first_name',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'First name is required.']),
                ],
                'translation_domain' => 'messages',
            ],
            'email' => [
                'label' => 'email',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Email is required.']),
                    new Email(['message' => 'Email is not valid.']),
                ],
                'translation_domain' => 'messages',
            ],
            'numtel' => [
                'label' => 'phone_number',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required.']),
                    new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Phone number must be exactly 8 digits.'
                    ]),
                ],
                'translation_domain' => 'messages',
            ],
        ];
        
        // Only set default data for new entities
        if ($isNew) {
            $fieldConfig['nom']['data'] = $userData['nom'];
            $fieldConfig['prenom']['data'] = $userData['prenom'];
            $fieldConfig['email']['data'] = $userData['email'];
            $fieldConfig['numtel']['data'] = $userData['numtel'];
        }

        $builder
            ->add('nom', TextType::class, $fieldConfig['nom'])
            ->add('prenom', TextType::class, $fieldConfig['prenom'])
            ->add('email', EmailType::class, $fieldConfig['email'])
            ->add('numtel', TelType::class, $fieldConfig['numtel'])
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
            ->add('status', ChoiceType::class, [
                'label' => 'status',
                'required' => true,
                'choices' => [
                    'En attente' => 'En attente',
                    'Confirmée' => 'Confirmée',
                    'Annulée' => 'Annulée',
                    'Terminée' => 'Terminée'
                ],
                'attr' => ['style' => $options['is_admin'] ? '' : 'display: none;'], // Show status for admins
                'label_attr' => ['style' => $options['is_admin'] ? '' : 'display: none;'],
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

        // Optionally adjust constraints for admin users
        if ($options['is_admin']) {
            // Example: Relax constraints or add admin-specific fields
            $fieldConfig['numtel']['constraints'] = []; // Remove phone number constraints for admins
            $builder->add('numtel', TelType::class, $fieldConfig['numtel']); // Re-add numtel with updated config
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationpersonnalise::class,
            'user_data' => null,
            'is_admin' => false, // Define is_admin option with default value
            'translation_domain' => 'messages',
        ]);

        $resolver->setAllowedTypes('user_data', ['array', 'null']);
        $resolver->setAllowedTypes('is_admin', 'bool'); // Restrict is_admin to boolean
    }
}