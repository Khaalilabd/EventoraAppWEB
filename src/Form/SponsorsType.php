<?php

namespace App\Form;

use App\Entity\Sponsor;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SponsorsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPartenaire', TextType::class, [
                'label' => 'Nom du partenaire',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom du partenaire est requis.',
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'help' => 'Entrez le nom officiel du partenaire (ex. : "Décorations Éclat").',
            ])
            ->add('emailPartenaire', EmailType::class, [
                'label' => 'Email du partenaire',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'email est requis.',
                    ]),
                    new Assert\Email([
                        'message' => 'L\'email doit être valide (ex. : contact@exemple.com).',
                    ]),
                ],
                'help' => 'Indiquez une adresse email valide pour contacter le partenaire.',
            ])
            ->add('telephonePartenaire', TextType::class, [
                'label' => 'Téléphone du partenaire',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le numéro de téléphone est requis.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\+?\d{8,15}$/',
                        'message' => 'Le numéro de téléphone doit contenir entre 8 et 15 chiffres (ex. : +21612345678 ou 12345678).',
                    ]),
                ],
                'help' => 'Entrez un numéro de téléphone valide (ex. : +21612345678).',
            ])
            ->add('adressePartenaire', TextType::class, [
                'label' => 'Adresse du partenaire',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'adresse est requise.',
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'L\'adresse doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'help' => 'Saisissez l\'adresse complète du partenaire.',
            ])
            ->add('siteWeb', TextType::class, [
                'label' => 'Site web',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le site web est requis.',
                    ]),
                    new Assert\Url([
                        'message' => 'L\'URL doit être valide (ex. : https://www.exemple.com).',
                    ]),
                ],
                'help' => 'Indiquez l\'URL du site web du partenaire (ex. : https://www.exemple.com).',
            ])
            ->add('typePartenaire', ChoiceType::class, [
                'label' => 'Type de partenaire',
                'choices' => [
                    'Décoration' => 'decoration',
                    'Photographe' => 'photographe',
                    'Traiteur' => 'traiteur',
                    'Sono' => 'sono',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Choisir un type',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner un type de partenaire.',
                    ]),
                ],
                'help' => 'Sélectionnez la catégorie du partenaire.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}