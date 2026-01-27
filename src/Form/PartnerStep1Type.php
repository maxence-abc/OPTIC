<?php

namespace App\Form;

use App\Dto\EstablishmentDraft;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PartnerStep1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l’établissement',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => true,
                'placeholder' => 'Choisir une catégorie',
                'choices' => [
                    'Restaurant' => 'restaurant',
                    'Coiffure' => 'coiffure',
                    'Santé' => 'sante',
                    'Sport' => 'sport',
                    'Automobile' => 'automobile',
                    'Bien-être' => 'bien-etre',
                ],
            ])
            ->add('professionalEmail', EmailType::class, [
                'label' => 'Email professionnel',
            ])
            ->add('professionalPhone', TextType::class, [
                'label' => 'Téléphone professionnel',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EstablishmentDraft::class,
            'validation_groups' => ['step1'],
        ]);
    }
}
