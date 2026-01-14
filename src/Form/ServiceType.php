<?php

namespace App\Form;

use App\Entity\Establishment;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom du service',
            ])
            ->add('description', null, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('duration', null, [
                'label' => 'Durée (minutes)',
            ])
            ->add('price', null, [
                'label' => 'Prix (€)',
            ])
            ->add('bufferTime', null, [
                'label' => 'Temps tampon (minutes)',
                'required' => false,
            ])
        ;

        // Champ établissement (affiché uniquement si non masqué)
        if (!$options['hide_establishment']) {
            $builder->add('establishment', EntityType::class, [
                'class' => Establishment::class,
                'choice_label' => function (Establishment $establishment) {
                    return $establishment->getName() . ' (' . $establishment->getCity() . ')';
                },
                'label' => 'Établissement',
                'placeholder' => 'Sélectionnez un établissement',
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
            'hide_establishment' => false,
        ]);
    }
}
