<?php

namespace App\Form;

use App\Entity\Establishment;
use App\Entity\OpeningHour;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpeningHourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dayOfWeek', ChoiceType::class, [
                'label' => 'Jour de la semaine',
                'placeholder' => 'Choisir un jour',
                'choices' => [
                    'Lundi' => 'Monday',
                    'Mardi' => 'Tuesday',
                    'Mercredi' => 'Wednesday',
                    'Jeudi' => 'Thursday',
                    'Vendredi' => 'Friday',
                    'Samedi' => 'Saturday',
                    'Dimanche' => 'Sunday',
                ],
                'required' => true,
            ])
            ->add('openTime', TimeType::class, [
                'label' => 'Heure d’ouverture',
                'widget' => 'single_text',
                'required' => true,
                // Optionnel mais souvent utile
                // 'minutes' => [0, 15, 30, 45],
            ])
            ->add('closeTime', TimeType::class, [
                'label' => 'Heure de fermeture',
                'widget' => 'single_text',
                'required' => true,
                // Optionnel mais souvent utile
                // 'minutes' => [0, 15, 30, 45],
            ])
        ;

        // Champ établissement (hors wizard)
        if (!$options['hide_establishment']) {
            $builder->add('establishment', EntityType::class, [
                'class' => Establishment::class,
                'choice_label' => 'name',
                'label' => 'Établissement',
                'placeholder' => 'Sélectionnez un établissement',
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpeningHour::class,
            'hide_establishment' => false,
        ]);
    }
}
