<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

final class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('rating', 'Note'),
            AssociationField::new('establishment', 'Établissement')
                ->setFormTypeOption('choice_label', 'name'),
            AssociationField::new('client', 'Client')
                ->setFormTypeOption('choice_label', 'email'),
            AssociationField::new('appointment', 'Réservation')
                ->setFormTypeOption('choice_label', static function ($appointment): string {
                    if ($appointment === null) {
                        return 'Réservation';
                    }

                    return sprintf(
                        '#%d - %s',
                        $appointment->getId() ?? 0,
                        $appointment->getDate()?->format('d/m/Y') ?? 'sans date'
                    );
                })
                ->hideOnIndex(),
            TextareaField::new('comment', 'Commentaire'),
            TextareaField::new('businessReply', 'Réponse établissement')->hideOnIndex(),
            DateTimeField::new('createdAt', 'Publié le')->hideOnForm(),
            DateTimeField::new('businessRepliedAt', 'Réponse envoyée le')->hideOnForm(),
        ];
    }
}
