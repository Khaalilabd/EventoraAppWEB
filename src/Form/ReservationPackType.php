<?php

namespace App\Form;

use App\Entity\Reservationpack;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Pack;

class ReservationPackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('numtel')
            ->add('description')
            ->add('pack', EntityType::class, [
                'class' => Pack::class,
                'choice_label' => 'id', // Utilise l'ID pour éviter de charger nomPack
                'placeholder' => 'Sélectionnez un pack',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->select('p.id') // Charge uniquement l'ID
                        ->orderBy('p.id', 'ASC');
                },
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationpack::class,
        ]);
    }
}