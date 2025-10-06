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
            ])
            ->add('duration', null, [
                'label' => 'Durée (minutes)',
            ])
            ->add('price', null, [
                'label' => 'Prix (€)',
            ])
            ->add('bufferTime', null, [
                'label' => 'Temps tampon (minutes)',
            ])
            ->add('establishment', EntityType::class, [
                'class' => Establishment::class,
                'choice_label' => function (Establishment $establishment) {
                    // On affiche le nom + la ville, plus clair dans un select
                    return $establishment->getName() . ' (' . $establishment->getCity() . ')';
                },
                'label' => 'Établissement',
                'placeholder' => 'Sélectionnez un établissement',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
