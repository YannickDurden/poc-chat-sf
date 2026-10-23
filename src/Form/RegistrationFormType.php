<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Identifiant',
                'attr' => ['autocomplete' => 'username'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'options' => ['attr' => ['autocomplete' => 'new-password']],
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
            ])
            ->add('role', EnumType::class, [
                'class' => UserRole::class,
                'label' => 'Rôle',
                'choice_label' => static fn (UserRole $role): string => match ($role) {
                    UserRole::USER => 'Utilisateur',
                    UserRole::ADMIN => 'Administrateur',
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
