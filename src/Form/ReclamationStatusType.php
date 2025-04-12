<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En attente' => Reclamation::STATUT_EN_ATTENTE, // Affiche "En attente", stocke "En_Attente"
                    'En cours' => Reclamation::STATUT_EN_COURS,     // Affiche "En cours", stocke "En_Cours"
                    'Résolu' => Reclamation::STATUT_RESOLU,         // Affiche "Résolu", stocke "Resolue"
                    'Rejeté' => Reclamation::STATUT_REJETE,         // Affiche "Rejeté", stocke "Rejetée"
                ],
                'label' => 'Statut',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}