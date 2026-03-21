<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

final class AppointmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Appointment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateField::new('date', 'Date'),
            TimeField::new('startTime', 'Début'),
            TimeField::new('endTime', 'Fin'),
            ChoiceField::new('status', 'Statut')->setChoices([
                'En attente' => 'pending',
                'Confirmé' => 'confirmed',
                'Annulé' => 'cancelled',
                'Terminé' => 'completed',
            ]),
            AssociationField::new('service', 'Service')
                ->setFormTypeOption('choice_label', 'name'),
            AssociationField::new('client', 'Client')
                ->setFormTypeOption('choice_label', 'email'),
            AssociationField::new('professional', 'Professionnel')
                ->setFormTypeOption('choice_label', 'email'),
            AssociationField::new('transferredBy', 'Transféré par')
                ->setFormTypeOption('choice_label', 'email')
                ->hideOnIndex(),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('transferredAt', 'Transféré le')->hideOnForm(),
        ];
    }
}
