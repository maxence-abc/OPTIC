<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Equipement;
use App\Entity\Establishment;
use App\Entity\Service;
use App\Repository\EquipementRepository;
use App\Repository\ServiceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champs de base (on met service "placeholder", on le reconfigure dynamiquement ensuite)
        $builder
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => fn(Service $s) => $s->getName(),
                'label' => 'Choisir un service',
                'placeholder' => 'Sélectionnez un service',
                'required' => true,
                // on surcharge via event pour filtrer
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date du rendez-vous',
                'required' => true,
            ])
            ->add('startTime', ChoiceType::class, [
                'label' => 'Créneau horaire disponible',
                'choices' => $options['available_slots'] ?? [],
                'placeholder' => 'Choisissez un créneau',
                'required' => true,
                'mapped' => false,
            ])
            ->add('equipement', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'name',
                'label' => 'Équipement (facultatif)',
                'placeholder' => 'Aucun équipement',
                'required' => false,
                // filtré via event aussi si possible
            ])
        ;

        // On filtre en fonction de l'établissement déduit depuis $appointment->getService()
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $appointment = $event->getData();
            $form = $event->getForm();

            if (!$appointment instanceof Appointment) {
                return;
            }

            $est = $appointment->getService()?->getEstablishment();
            if (!$est instanceof Establishment) {
                // Pas d'établissement déductible => on ne filtre pas (ou tu peux bloquer)
                return;
            }

            // Service filtré par établissement
            $form->add('service', EntityType::class, [
                'class' => Service::class,
                'query_builder' => function (ServiceRepository $repo) use ($est) {
                    return $repo->createQueryBuilder('s')
                        ->andWhere('s.establishment = :est')
                        ->setParameter('est', $est)
                        ->orderBy('s.name', 'ASC');
                },
                'choice_label' => fn(Service $s) => $s->getName(),
                'label' => 'Choisir un service',
                'placeholder' => 'Sélectionnez un service',
                'required' => true,
            ]);

            // Equipements filtrés par établissement (cohérence)
            $form->add('equipement', EntityType::class, [
                'class' => Equipement::class,
                'query_builder' => function (EquipementRepository $repo) use ($est) {
                    return $repo->createQueryBuilder('eq')
                        ->andWhere('eq.establishment = :est')
                        ->setParameter('est', $est)
                        ->orderBy('eq.name', 'ASC');
                },
                'choice_label' => 'name',
                'label' => 'Équipement (facultatif)',
                'placeholder' => 'Aucun équipement',
                'required' => false,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'available_slots' => [],
        ]);
    }
}
