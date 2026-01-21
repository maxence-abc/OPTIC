<?php

namespace App\Form;

use App\Dto\EstablishmentDraft;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartnerStep3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre établissement',
                'required' => true,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Décrivez votre établissement, votre expertise, votre équipe...',
                ],
            ])

            ->add('services', CollectionType::class, [
                'entry_type' => ServiceType::class,
                'entry_options' => [
                    'hide_establishment' => true,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
                'required' => false,
            ])

            ->add('openingHours', CollectionType::class, [
                'entry_type' => OpeningHourType::class,
                'entry_options' => [
                    'hide_establishment' => true,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EstablishmentDraft::class,
            'validation_groups' => ['step3'],
        ]);
    }
}
