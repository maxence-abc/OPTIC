<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Dom\Text;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
         return [
        TextField::new('email'),
        TextField::new('first_name'),
        TextField::new('last_name'),
        DateField::new('created_at'),
        DateField::new('update_at'),
        TextField::new('password')->hideOnIndex(),
        ArrayField::new('roles'),
        ];
    }
    
}
