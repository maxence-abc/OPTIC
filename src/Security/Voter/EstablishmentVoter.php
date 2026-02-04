<?php

namespace App\Security\Voter;

use App\Entity\Establishment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class EstablishmentVoter extends Voter
{
    public const MANAGE = 'ESTABLISHMENT_MANAGE';
    public const VIEW   = 'ESTABLISHMENT_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::MANAGE, self::VIEW], true)
            && $subject instanceof Establishment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Establishment $establishment */
        $establishment = $subject;

        // Admin global = OK
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Manager : doit être owner
        if (in_array('ROLE_ADMIN_PRO', $user->getRoles(), true)) {
            return $establishment->getOwner()?->getId() === $user->getId();
        }

        // Pro normal : peut voir seulement si attaché à l'établissement
        if (in_array('ROLE_PRO', $user->getRoles(), true)) {
            return $user->getEstablishment()?->getId() === $establishment->getId();
        }

        return false;
    }
}
