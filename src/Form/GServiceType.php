<?php

namespace App\Form;

use App\Entity\GService;
use App\Entity\Sponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class GServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sponsor', EntityType::class, [
                'label' => 'Partenaire (Sponsor)',
                'class' => Sponsor::class,
                'choice_label' => 'nom_partenaire',
                'placeholder' => 'Sélectionner un sponsor',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner un partenaire.',
                    ]),
                ],
                'help' => 'Choisissez le partenaire qui propose ce service.',
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre du service',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le titre du service est requis.',
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'help' => 'Entrez un titre descriptif pour le service (ex. : "Décoration de mariage").',
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Lieu',
                'choices' => [
                    'Hôtel' => 'hotel',
                    'Maison d\'hôte' => 'maison_d_hote',
                    'Espace vert' => 'espace_vert',
                    'Salle de fête' => 'salle_de_fete',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisissez un lieu',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner un lieu.',
                    ]),
                ],
                'help' => 'Indiquez où le service sera fourni.',
            ])
            ->add('typeService', ChoiceType::class, [
                'label' => 'Type de service',
                'choices' => [
                    'Décoration' => 'decoration',
                    'Lumière' => 'lumiere',
                    'Sono' => 'sono',
                    'Traiteur' => 'traiteur',
                    'Fleuriste' => 'fleuriste',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisissez un type',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner un type de service.',
                    ]),
                ],
                'help' => 'Sélectionnez la catégorie du service.',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description est requise.',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'help' => 'Décrivez en détail ce que comprend le service.',
            ])
            ->add('prix', TextType::class, [
                'label' => 'Prix',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le prix est requis.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\d+(\.\d{1,2})?\s*(dt|Dt)$/',
                        'message' => 'Le prix doit être au format "99.99 dt" ou "99.99 Dt".',
                    ]),
                    new Assert\Callback([
                        'callback' => function ($value, $context) {
                            if (is_string($value)) {
                                // Extraire la partie numérique
                                $numericValue = (float) preg_replace('/\s*(dt|Dt)$/', '', $value);
                                if ($numericValue < 0) {
                                    $context->buildViolation('Le prix ne peut pas être négatif.')
                                        ->addViolation();
                                }
                            }
                        },
                    ]),
                ],
                'help' => 'Entrez le prix en dinars tunisiens (ex. : 99.99 dt).',
                'attr' => [
                    'pattern' => '\d+(\.\d{1,2})?\s*(dt|Dt)', // Validation côté client
                    'title' => 'Entrez un prix au format 99.99 dt ou 99.99 Dt',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'required' => false,
                'mapped' => false, // Géré manuellement dans le contrôleur
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG ou PNG).',
                    ]),
                ],
                'help' => 'Uploadez une image pour le service (max 2 Mo, formats JPEG ou PNG).',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GService::class,
            'attr' => ['novalidate' => 'novalidate'], // Désactive la validation HTML5
        ]);
    }
}