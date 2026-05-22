<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SecurityEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()?->getUser();
        if (!$user) {
            return;
        }

        $log = new ActivityLog();
        $log->setUserId(method_exists($user, 'getId') ? $user->getId() : null);
        $log->setUsername(method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (method_exists($user, 'getUsername') ? $user->getUsername() : null));
        $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];
        $log->setRole(is_array($roles) && count($roles) ? $roles[0] : null);
        $log->setAction('LOGIN');
        $log->setTarget('User login');

        $this->em->persist($log);
        $this->em->flush();
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if (!$token instanceof TokenInterface) {
            return;
        }

        $user = $token->getUser();
        if (!$user) {
            return;
        }

        $log = new ActivityLog();
        $log->setUserId(method_exists($user, 'getId') ? $user->getId() : null);
        $log->setUsername(method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (method_exists($user, 'getUsername') ? $user->getUsername() : null));
        $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];
        $log->setRole(is_array($roles) && count($roles) ? $roles[0] : null);
        $log->setAction('LOGOUT');
        $log->setTarget('User logout');

        $this->em->persist($log);
        $this->em->flush();
    }
}
