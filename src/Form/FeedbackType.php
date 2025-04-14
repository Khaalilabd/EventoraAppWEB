<?php

namespace App\Form;

use App\Entity\Feedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Validator\Constraints\File;

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
                'attr' => ['class' => 'form-control star-rating', 'id' => 'feedback_vote'],
                'required' => true,
            ])
            ->add('Description', TextareaType::class, [
                'label' => 'Votre expérience',
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'required' => true,
            ])
            ->add('souvenirsFile', FileType::class, [
                'label' => 'Uploader une image (optionnel)',
                'mapped' => false, // Changement ici
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG ou PNG).',
                    ])
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true,
                ],
                'required' => false,
                'disabled' => true,
            ])
            ->add('Recommend', CheckboxType::class, [
                'label' => 'Recommanderiez-vous notre service ?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'mapped' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $feedback = $event->getData();
            $form = $event->getForm();

            if (!$feedback instanceof Feedback) {
                return;
            }

            $form->get('Recommend')->setData($feedback->getRecommend() === 'Oui');
        });

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