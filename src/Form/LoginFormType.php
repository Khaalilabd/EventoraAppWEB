<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank; // Importez la contrainte NotBlank

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Email'],
                'constraints' => [ // Ajoutez les contraintes ici
                    new NotBlank([
                        'message' => 'Veuillez renseigner votre email.', // Message d'erreur personnalisé si le champ est vide
                    ]),
                ],
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => ['placeholder' => 'Mot de passe'],
                'constraints' => [ // Ajoutez les contraintes ici
                    new NotBlank([
                        'message' => 'Veuillez renseigner votre mot de passe.', // Message d'erreur personnalisé si le champ est vide
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }


}