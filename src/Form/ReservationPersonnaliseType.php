<?php

namespace App\Form;

use App\Entity\Reservationpersonnalise;
use App\Entity\GService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ReservationPersonnaliseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('numtel')
            ->add('description')
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => true,
            ])
            ->add('services', EntityType::class, [
                'class' => GService::class,
                'choice_label' => fn(GService $service) => sprintf('%s - %s - %s', $service->getTitre(), $service->getPrix(), $service->getLocation()),
                'multiple' => true,
                'expanded' => true, // Rendre le champ sous forme de cases à cocher
                'required' => true, // S'assurer qu'au moins un service est sélectionné
                'label' => 'Services (sélectionnez au moins un service)',
                'attr' => [
                    'class' => 'checkbox-services', // Classe CSS pour stylisation
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservationpersonnalise::class,
        ]);
    }
}