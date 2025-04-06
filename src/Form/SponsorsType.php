<?php

namespace App\Form;
use App\Entity\Sponsor;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SponsorsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPartenaire', TextType::class)
            ->add('emailPartenaire', EmailType::class)
            ->add('telephonePartenaire', TextType::class)
            ->add('adressePartenaire', TextType::class)
            ->add('siteWeb', TextType::class)
            ->add('typePartenaire', ChoiceType::class, [
                'choices' => [
                    'Decoration' => 'Sponsor',
                    'Photographe' => 'Photographe',
                    'Traiteur' => 'Traiteur',
                    'Sono' => 'Sono',
                    'Autre' => 'Autre',
                ],
                'placeholder' => 'Choisir un type',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}