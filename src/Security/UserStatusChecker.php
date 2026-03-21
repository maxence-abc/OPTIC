<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserStatusChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isActive() === false) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été désactivé. Contactez l’équipe OPTIC pour le réactiver.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
