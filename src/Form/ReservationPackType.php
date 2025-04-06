<?php

namespace App\Form;

use App\Entity\ReservationPack;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationPackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idPack')
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('numtel')
            ->add('description')
            ->add('date')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationPack::class,
        ]);
    }
}