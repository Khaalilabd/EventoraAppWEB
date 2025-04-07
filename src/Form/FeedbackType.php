<?php

namespace App\Form;

use App\Entity\Feedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Vote', ChoiceType::class, [
                'choices' => [
                    '1 étoile' => 1,
                    '2 étoiles' => 2,
                    '3 étoiles' => 3,
                    '4 étoiles' => 4,
                    '5 étoiles' => 5,
                ],
                'label' => 'Note (1 à 5 étoiles)',
                'attr' => ['class' => 'form-select star-rating'],
                'required' => true,
            ])
            ->add('Description', TextareaType::class, [
                'label' => 'Votre expérience',
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'required' => true,
            ])
            ->add('souvenirsFile', FileType::class, [
                'label' => 'Uploader une image (optionnel)',
                'mapped' => true,
                'required' => false,
                'attr' => ['class' => 'form-control-file'],
            ])
            ->add('Recommend', CheckboxType::class, [
                'label' => 'Recommanderiez-vous notre service ?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'mapped' => false, // On gère manuellement ce champ
            ]);

        // Événement pour transformer la case à cocher en "Oui" ou "Non"
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $feedback = $event->getData();
            $recommend = $form->get('Recommend')->getData();
            $feedback->setRecommend($recommend ? 'Oui' : 'Non');
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
        ]);
    }
}