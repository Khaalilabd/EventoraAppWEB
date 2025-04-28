<?php

namespace App\Form;

use App\Entity\Reservationpack;
use App\Entity\Pack;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        // Fetch initial packs (e.g., first 50, ordered by nomPack)
        $initialPacks = $this->entityManager->getRepository(Pack::class)
            ->createQueryBuilder('p')
            ->orderBy('p.nomPack', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('prenom', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est requis.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est requis.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'email ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('numtel', TelType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone est requis.']),
                    new Length([
                        'min' => 8,
                        'max' => 8,
                        'minMessage' => 'Le numéro de téléphone doit contenir exactement 8 chiffres.',
                        'maxMessage' => 'Le numéro de téléphone doit contenir exactement 8 chiffres.',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => 'Le numéro de téléphone doit contenir exactement 8 chiffres (ex. 12345678).',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '12345678',
                ],
                'help' => 'Entrez un numéro tunisien de 8 chiffres (ex. 12345678).',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5],
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pack', EntityType::class, [
                'class' => Pack::class,
                'choice_label' => 'nomPack',
                'choices' => $initialPacks,
                'constraints' => [
                    new NotBlank(['message' => 'Vous devez sélectionner un pack.']),
                ],
                'attr' => [
                    'class' => 'form-control select2',
                    'data-ajax--url' => '/user/reservations/pack/search',
                    'data-ajax--delay' => 250,
                    'data-minimum-input-length' => 0,
                ],
            ]);

        if ($options['is_admin']) {
            $builder->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'En attente',
                    'Validé' => 'Validé',
                    'Refusé' => 'Refusé',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le statut est requis.']),
                ],
                'attr' => ['class' => 'form-control'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationpack::class,
            'is_admin' => false,
            'step' => 1,
        ]);
    }
}