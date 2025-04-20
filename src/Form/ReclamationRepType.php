<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Entity\ReclamationRep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ReclamationRepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reponse', TextareaType::class, [
                'label' => 'Réponse',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Nouveau Statut',
                'choices' => [
                    'En Cours' => Reclamation::STATUT_EN_COURS,
                    'Résolue' => Reclamation::STATUT_RESOLU,
                    'Rejetée' => Reclamation::STATUT_REJETE,
                ],
                'required' => true,
                'mapped' => false, // Ne pas mapper ce champ à ReclamationRep
                'attr' => ['class' => 'form-control'],
            ]);

        // Événement pour mettre à jour le statut de l'entité Reclamation
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $reclamation = $options['reclamation'];

            // Mettre à jour le statut de l'entité Reclamation
            if ($form->has('statut') && $reclamation) {
                $reclamation->setStatut($form->get('statut')->getData());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReclamationRep::class,
            'reclamation' => null,
        ]);

        $resolver->setRequired('reclamation');
    }
}