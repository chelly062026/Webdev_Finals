<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderNotificationSubscriber implements EventSubscriberInterface
{
    private HttpClientInterface $httpClient;
    private string $websocketUrl;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // The Node.js server address
        $this->websocketUrl = 'http://localhost:8080/notify';
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Order) {
            return;
        }

        $this->sendNotification($entity);
    }

    private function sendNotification(Order $order): void
    {
        try {
            $total = $order->getTotalAmount() ?? 0;
            $customer = $order->getCustomer() ? $order->getCustomer()->getName() : 'Someone';
            
            $this->httpClient->request('POST', $this->websocketUrl, [
                'json' => [
                    'title' => 'New Order Received! 🛍️',
                    'body' => sprintf('%s just placed an order for ₱%s.', $customer, number_format($total, 2)),
                    'data' => [
                        'orderId' => $order->getId(),
                        'type' => 'new_order'
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            // Silently fail or log the error
            // In a real app, you might want to use Messenger to retry
        }
    }
}
