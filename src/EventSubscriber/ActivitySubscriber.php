<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em, private TokenStorageInterface $tokenStorage)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::postPersist => 'postPersist',
            Events::postUpdate => 'postUpdate',
            Events::postRemove => 'postRemove',
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args, 'CREATE');
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args, 'UPDATE');
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args, 'DELETE');
    }

    private function handleEvent(LifecycleEventArgs $args, string $action): void
    {
        $entity = $args->getObject();

        // Only log domain entities (Product, Customer, Order, User)
        $class = get_class($entity);
        $allowed = [
            'App\\Entity\\Product',
            'App\\Entity\\Customer',
            'App\\Entity\\Order',
            'App\\Entity\\User',
        ];

        if (!in_array($class, $allowed, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        $log = new ActivityLog();
        $log->setUserId($user && method_exists($user, 'getId') ? $user->getId() : null);
        $log->setUsername($user && method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : ($user && method_exists($user, 'getUsername') ? $user->getUsername() : null));
        $roles = $user && method_exists($user, 'getRoles') ? $user->getRoles() : [];
        $log->setRole(is_array($roles) && count($roles) ? $roles[0] : null);
        $log->setAction($action);

        // Build target string like "Product: Name (ID: 3)"
        $short = (new \ReflectionClass($entity))->getShortName();
        $id = null;
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
        }

        $label = $short;
        if (method_exists($entity, 'getName')) {
            $label .= ': ' . $entity->getName();
        } elseif (method_exists($entity, 'getUsername')) {
            $label .= ': ' . $entity->getUsername();
        } elseif (method_exists($entity, 'getProduct')) {
            $products = $entity->getProduct();
            $names = [];
            foreach ($products as $p) {
                if (method_exists($p, 'getName')) {
                    $names[] = $p->getName();
                }
            }
            if (count($names) > 0) {
                $label .= ': ' . implode(', ', array_slice($names, 0, 3));
                if (count($names) > 3) {
                    $label .= ' +' . (count($names) - 3) . ' more';
                }
            }
        }

        if ($id) {
            $label .= ' (ID: ' . $id . ')';
        }

        $log->setTarget($label);

        $this->em->persist($log);
        $this->em->flush();
    }
}
