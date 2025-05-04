<?php

namespace App\Form;

use App\Entity\Membre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
// use Symfony\Component\Validator\Constraints\DateTime; // This constraint is rarely needed on a DateType with single_text widget

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'last_name',
                'constraints' => [
                    new NotBlank(['message' => 'Last name is required.']),
                    new Length(['min' => 2, 'minMessage' => 'Last name must contain at least {{ limit }} characters.']),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'first_name',
                'constraints' => [
                    new NotBlank(['message' => 'First name is required.']),
                    new Length(['min' => 2, 'minMessage' => 'First name must contain at least {{ limit }} characters.']),
                ],
            ])
            ->add('dateOfBirth', DateType::class, [
                'label' => 'date_of_birth',
                'required' => false, // NotBlank validation would be done on the entity if needed, but FormType has LessThanOrEqual
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Select a date (YYYY-MM-DD format)',
                ],
                'constraints' => [
                    new LessThanOrEqual([
                        'value' => '2007-04-25', // Make sure this is the correct date for 18 years
                        'message' => 'You must be at least 18 years old to register.',
                    ]),
                    // If date of birth is mandatory, add NotBlank here or on the entity
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'gender',
                'required' => false,
                'choices' => [
                    'Male' => 'Homme',
                    'Female' => 'Femme',
                ],
                'placeholder' => 'Select your gender (optional)',
                'attr' => [
                    'class' => 'form-select',
                ],
                // If gender is required, add NotBlank here or on the entity
            ])
            ->add('email', EmailType::class, [
                'label' => 'email',
                'constraints' => [
                    new NotBlank(['message' => 'Email is required.']),
                    new \Symfony\Component\Validator\Constraints\Email(['message' => 'The email address "{{ value }}" is not valid.']), // Use FQCN or a use statement
                ],
            ])
            ->add('cin', TextType::class, [
                'label' => 'cin',
                'constraints' => [
                    new NotBlank(['message' => 'National ID is required.']),
                     new Regex([
                        'pattern' => '/^[0-9]{8}$/',
                        'message' => "National ID number must be exactly 8 digits."
                    ]),
                ],
            ])
            ->add('numTel', TextType::class, [
                'label' => 'phone_number',
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required.']),
                     new Regex([
                        'pattern' => '/^[24579][0-9]{7}$/',
                        'message' => "Please enter a valid Tunisian phone number (8 digits, starting with 2, 4, 5, 7 or 9)."
                    ]),
                ],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'address',
                'constraints' => [
                    new NotBlank(['message' => 'Address is required.']),
                     new Length(['min' => 5, 'minMessage' => 'The full address must contain at least {{ limit }} characters.']),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'username',
                'required' => false, // Make sure this field is optional if required: false is used
                // If the username has constraints (min length, allowed characters), add them here or on the entity
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => $options['is_edit'] ? 'password.edit' : 'password',
                'required' => !$options['is_edit'],
                // Remove 'mapped' => false to map directly to the entity
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank(['message' => 'Password is required.']),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password must contain at least {{ limit }} characters.',
                    ]),
                ],
            ])

            ->add('image', FileType::class, [
                'label' => 'profile_picture',
                'required' => false,
                'mapped' => false, // Don't map directly to the entity (handle upload separately)
                // If you want to validate the file type or size, add Assert\File or Assert\Image here
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
            'is_edit' => false, // Option to manage edit mode
            'translation_domain' => 'messages'
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }

    // Make sure that the validation of Membre entity constraints is also activated if you don't put *all*
    // constraints in the FormType. Often, basic constraints are defined on the entity and form-specific
    // constraints in the FormType.
    // You can check in your configuration (config/packages/validator.yaml) that validation.enable_annotations is true.
}