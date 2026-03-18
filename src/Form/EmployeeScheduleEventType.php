<?php

namespace App\Form;

use App\Entity\EmployeeScheduleEvent;
use App\Entity\Establishment;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeScheduleEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Establishment|null $establishment */
        $establishment = $options['establishment'];
        $employees = $establishment?->getUsers()->toArray() ?? [];

        if ($establishment?->getOwner() instanceof User && !in_array($establishment->getOwner(), $employees, true)) {
            $employees[] = $establishment->getOwner();
        }

        if ($options['show_employee']) {
            $builder->add('employee', EntityType::class, [
                'class' => User::class,
                'choices' => $employees,
                'choice_label' => static fn (User $user): string => trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName())),
                'label' => 'Employé',
                'placeholder' => 'Sélectionner un employé',
            ]);
        }

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Horaire personnalisé' => EmployeeScheduleEvent::TYPE_WORK,
                    'Repos' => EmployeeScheduleEvent::TYPE_REST,
                    'Congé' => EmployeeScheduleEvent::TYPE_LEAVE,
                    'Formation' => EmployeeScheduleEvent::TYPE_TRAINING,
                ],
                'label' => 'Type d’événement',
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime',
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployeeScheduleEvent::class,
            'establishment' => null,
            'show_employee' => true,
        ]);

        $resolver->setAllowedTypes('establishment', ['null', Establishment::class]);
        $resolver->setAllowedTypes('show_employee', 'bool');
    }
}
