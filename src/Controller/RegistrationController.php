<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $user->setIsActive(true);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Hash du mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword($user, $user->getPassword())
            );

            // accountType: "client" ou "pro"
            $accountType = (string) $form->get('accountType')->getData();
            $accountType = strtolower(trim($accountType));

            // Un seul rôle métier en base
            if ($accountType === 'pro') {
                $user->setRoles(['ROLE_PRO']);
            } else {
                $user->setRoles(['ROLE_CLIENT']);
            }

            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdateAt(new \DateTime());

            $em->persist($user);
            $em->flush();


            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
