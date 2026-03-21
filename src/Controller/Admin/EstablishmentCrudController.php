<?php

namespace App\Controller\Admin;

use App\Entity\Establishment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class EstablishmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Establishment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('category', 'Catégorie'),
            TextField::new('address', 'Adresse'),
            TextField::new('postalCode', 'Code postal'),
            TextField::new('city', 'Ville'),
            TextField::new('professionalEmail', 'Email pro')->hideOnIndex(),
            TextField::new('professionalPhone', 'Téléphone pro')->hideOnIndex(),
            AssociationField::new('owner', 'Propriétaire')
                ->setFormTypeOption('choice_label', 'email'),
            IntegerField::new('servicesCount', 'Services')
                ->hideOnForm(),
            IntegerField::new('reviewsCount', 'Avis')
                ->hideOnForm(),
            TextareaField::new('description', 'Description')->hideOnIndex(),
        ];
    }
}
