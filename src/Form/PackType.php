<?php

namespace App\Form;

use App\Entity\Pack;
use App\Entity\GService;
use App\Entity\Typepack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PackType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPack', TextType::class, [
                'label' => 'Nom du pack',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Lieu',
                'choices' => [
                    'Hôtel' => 'HOTEL',
                    'Maison d\'hôte' => 'MAISON_D_HOTE',
                    'Espace vert' => 'ESPACE_VERT',
                    'Salle de fête' => 'SALLE_DE_FETE',
                    'Autre' => 'AUTRE',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un lieu',
            ])
            ->add('nbrGuests', NumberType::class, [
                'label' => 'Nombre d\'invités',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('image_path', FileType::class, [
                'label' => 'Image du pack',
                'attr' => ['class' => 'form-control-file', 'id' => 'pack_image_path'],
                'required' => false,
                'mapped' => false, // Géré manuellement dans le contrôleur
            ])
            ->add('typepack', EntityType::class, [
                'class' => Typepack::class,
                'choice_label' => 'type',
                'label' => 'Type de pack',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un type',
                'mapped' => false,
            ])
            ->add('services', EntityType::class, [
                'class' => GService::class,
                'choice_label' => 'titre',
                'label' => 'Services',
                'multiple' => true,
                'expanded' => true,
            ]);

        // Événement pour synchroniser typepack avec type
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $pack = $event->getData();
            $form = $event->getForm();
            $typepack = $form->get('typepack')->getData();

            if ($typepack instanceof Typepack) {
                $pack->setType($typepack->getType());
                $pack->setTypepack($typepack);
            }
        });

        // Événement pour initialiser typepack à partir de type
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $pack = $event->getData();
            $form = $event->getForm();

            if ($pack && $pack->getType()) {
                $typepack = $this->entityManager
                    ->getRepository(Typepack::class)
                    ->findOneBy(['type' => $pack->getType()]);
                if ($typepack) {
                    $pack->setTypepack($typepack);
                    $form->get('typepack')->setData($typepack);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pack::class,
            'service_choices' => [
                'Photographe' => 'Photographe',
                'DJ' => 'DJ',
                'Traiteur' => 'Traiteur',
                'Décoration' => 'Décoration',
                'Animateur' => 'Animateur',
            ],
        ]);
    }
}