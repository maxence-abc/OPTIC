<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ManagerEmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email de l’utilisateur',
                'required' => true,
                'help' => 'L’utilisateur doit déjà avoir un compte sur le site.',
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone (optionnel)',
                'required' => false,
                'help' => 'Si le compte n’a pas de téléphone, on peut le compléter.',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'data' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // IMPORTANT: on ne mappe sur aucune entity, c’est un form “de recherche/rattachement”
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
