<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Service;
use App\Entity\Equipement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Date du rendez-vous
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date du rendez-vous',
                'required' => true,
            ])

            // Service concerné
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => function (Service $service) {
                    $establishmentName = $service->getEstablishment()?->getName() ?? 'Aucun établissement';
                    return sprintf('%s — %s', $service->getName(), $establishmentName);
                },
                'label' => 'Choisir un service',
                'placeholder' => 'Sélectionnez un service',
                'required' => true,
            ])

            // Créneau disponible
            ->add('startTime', ChoiceType::class, [
                'label' => 'Créneau horaire disponible',
                'choices' => $options['available_slots'] ?? [],
                'placeholder' => 'Choisissez un créneau',
                'required' => true,
                'mapped' => false,
            ])

            // Équipement optionnel
            ->add('equipement', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'name',
                'label' => 'Équipement (facultatif)',
                'placeholder' => 'Aucun équipement',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'available_slots' => [],
        ]);
    }
}
