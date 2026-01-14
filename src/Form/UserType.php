<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- Ton form existant ---
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('phone')
            ->add('password')

            // --- Nouveau : type de compte (non mappé) ---
            ->add('accountType', ChoiceType::class, [
                'mapped' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => 'client', // default = Client
                'choices' => [
                    'Client' => 'client',
                    'Professionnel' => 'pro',
                ],
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
