<?php

namespace App\Form;

use App\Entity\Reservationpersonnalise;
use App\Entity\GService;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Count;

class ReservationPersonnaliseType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get user data from options, or default empty values
        $userData = $options['user_data'] ?? [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'numtel' => ''
        ];

        // Fetch initial services
        $initialServices = $this->entityManager->getRepository(GService::class)
            ->createQueryBuilder('s')
            ->orderBy('s.titre', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        // Check if we're creating a new entity (no ID) or editing an existing one
        $isNew = !$builder->getData() || !$builder->getData()->getIDReservationPersonalise();
        
        // Only set default data for new entities
        $fieldConfig = [
            'nom' => [
                'label' => 'last_name',
                'constraints' => [
                    new NotBlank(['message' => 'Last name is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Last name cannot exceed {{ limit }} characters.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
                'translation_domain' => 'messages',
            ],
            'prenom' => [
                'label' => 'first_name',
                'constraints' => [
                    new NotBlank(['message' => 'First name is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'First name cannot exceed {{ limit }} characters.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
                'translation_domain' => 'messages',
            ],
            'email' => [
                'label' => 'email',
                'constraints' => [
                    new NotBlank(['message' => 'Email is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Email cannot exceed {{ limit }} characters.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
                'translation_domain' => 'messages',
            ],
            'numtel' => [
                'label' => 'phone_number',
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required.']),
                    new Length([
                        'min' => 8,
                        'max' => 8,
                        'minMessage' => 'Phone number must be exactly 8 digits.',
                        'maxMessage' => 'Phone number must be exactly 8 digits.',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Phone number must be exactly 8 digits (e.g. 12345678).',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '12345678',
                ],
                'help' => 'Enter an 8-digit Tunisian phone number (e.g. 12345678).',
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
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'constraints' => [
                    new NotBlank(['message' => 'Description is required.']),
                ],
                'translation_domain' => 'messages',
            ])
            ->add('date', DateType::class, [
                'label' => 'event_date',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
                'translation_domain' => 'messages',
            ])
            ->add('services', EntityType::class, [
                'label' => 'services',
                'class' => GService::class,
                'choice_label' => 'titre',
                'choices' => $initialServices,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'You must select at least one service.'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control select2',
                    'data-ajax--url' => '/user/reservations/service/search',
                    'data-ajax--delay' => 250,
                    'data-minimum-input-length' => 0,
                ],
                'translation_domain' => 'messages',
            ]);

        if ($options['is_admin']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'status',
                'choices' => [
                    'Pending' => 'En attente',
                    'Approved' => 'Validé',
                    'Rejected' => 'Refusé',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Status is required.']),
                ],
                'attr' => ['class' => 'form-control'],
                'translation_domain' => 'messages',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationpersonnalise::class,
            'user_data' => null,
            'translation_domain' => 'messages',
            'is_admin' => false,
        ]);

        $resolver->setAllowedTypes('user_data', ['array', 'null']);
        $resolver->setAllowedTypes('is_admin', ['bool']);
    }
}