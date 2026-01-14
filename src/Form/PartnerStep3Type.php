<?php

namespace App\Form;

use App\Dto\EstablishmentDraft;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PartnerStep3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 6,
                ],
            ])
            ->add('services', TextareaType::class, [
                'label' => 'Services proposés',
                'help' => 'Ex: Coupe, Barbe, Brushing… (un par ligne ou séparé par des virgules)',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                ],
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
