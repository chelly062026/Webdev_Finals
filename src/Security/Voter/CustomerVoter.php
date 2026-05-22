<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CustomerVoter extends Voter
{
    public const IS_CUSTOMER = 'IS_CUSTOMER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::IS_CUSTOMER;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        return !$this->hasRole($token, 'ROLE_ADMIN')
            && !$this->hasRole($token, 'ROLE_STAFF');
    }

    private function hasRole(TokenInterface $token, string $role): bool
    {
        return in_array($role, $token->getRoleNames(), true);
    }
}
