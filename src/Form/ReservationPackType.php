<?php

namespace App\Form;

use App\Entity\Reservationpack;
use App\Entity\Pack;
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
use Doctrine\ORM\EntityManagerInterface;

class ReservationPackType extends AbstractType
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

        // Fetch initial packs (e.g., first 50, ordered by nomPack)
        $initialPacks = $this->entityManager->getRepository(Pack::class)
            ->createQueryBuilder('p')
            ->orderBy('p.nomPack', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        // Check if we're creating a new entity (no ID) or editing an existing one
        $isNew = !$builder->getData() || !$builder->getData()->getIDReservationPack();
        
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
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5],
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
            ->add('pack', EntityType::class, [
                'label' => 'select_pack',
                'class' => Pack::class,
                'choice_label' => 'nomPack',
                'choices' => $initialPacks,
                'constraints' => [
                    new NotBlank(['message' => 'You must select a pack.']),
                ],
                'attr' => [
                    'class' => 'form-control select2',
                    'data-ajax--url' => '/user/reservations/pack/search',
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
            'data_class' => Reservationpack::class,
            'is_admin' => false,
            'step' => 1,
            'user_data' => null, // Option to pass user data
            'translation_domain' => 'messages',
        ]);

        $resolver->setAllowedTypes('user_data', ['array', 'null']);
    }
}