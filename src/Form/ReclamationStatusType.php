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
                    'En attente' => 'en_attente',  // Clé = affichage, Valeur = ce qui est stocké
                    'En cours' => 'en_cours',
                    'Résolu' => 'resolu',
                    'Rejetée' => 'rejetée'
                ],
                'label' => 'Statut',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'placeholder' => 'Choisir un statut', // Optionnel, pour éviter une soumission vide
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}