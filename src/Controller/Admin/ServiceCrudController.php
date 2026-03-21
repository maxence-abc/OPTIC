<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ServiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Service::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            AssociationField::new('establishment', 'Établissement')
                ->setFormTypeOption('choice_label', 'name'),
            IntegerField::new('duration', 'Durée (min)'),
            MoneyField::new('price', 'Prix')->setCurrency('EUR')->setStoredAsCents(false),
            IntegerField::new('bufferTime', 'Temps tampon (min)'),
            TextareaField::new('description', 'Description')->hideOnIndex(),
        ];
    }
}
