<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Establishment;
use App\Entity\Service;
use App\Entity\Appointment;
use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $url = $this->container
            ->get(AdminUrlGenerator::class)
            ->setController(UserCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('OPTIC Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Établissements', 'fas fa-store', Establishment::class);
        yield MenuItem::linkToCrud('Services', 'fas fa-scissors', Service::class);
        yield MenuItem::linkToCrud('Réservations', 'fas fa-calendar-check', Appointment::class);
        yield MenuItem::linkToCrud('Avis', 'fas fa-star', Review::class);
        yield MenuItem::linkToRoute('Modération comptes', 'fas fa-user-shield', 'app_admin_user_index');
        yield MenuItem::linkToRoute('Retour au site', 'fas fa-home', 'app_home');
    }
}
