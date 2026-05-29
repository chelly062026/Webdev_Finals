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

    public function __construct(HttpClientInterface $httpClient, string $websocketNotifyUrl)
    {
        $this->httpClient = $httpClient;
        $this->websocketUrl = $websocketNotifyUrl;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args, 'order.created');
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args, 'order.updated');
    }

    private function handleEvent(LifecycleEventArgs $args, string $type): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Order) {
            return;
        }

        $this->sendNotification($entity, $type);
    }

    private function sendNotification(Order $order, string $type): void
    {
        try {
            $total = $order->getTotalAmount() ?? 0;
            $customer = $order->getCustomer() ? $order->getCustomer()->getName() : 'Someone';
            
            $response = $this->httpClient->request('POST', $this->websocketUrl, [
                'json' => [
                    'type' => $type,
                    'title' => 'Order Update! 🛍️',
                    'body' => $type === 'order.created' 
                        ? sprintf('%s just placed an order for ₱%s.', $customer, number_format((float)$total, 2))
                        : sprintf('Order #%d status updated.', $order->getId()),
                    'data' => [
                        'orderId' => $order->getId(),
                        'type' => $type,
                        'status' => $order->getStatus()
                    ]
                ],
            ]);

            // Optional: Log status for debugging
            if ($response->getStatusCode() !== 200) {
                error_log("WebSocket notification failed with status: " . $response->getStatusCode());
            } else {
                error_log("WebSocket notification sent successfully for order #" . $order->getId());
            }
        } catch (\Exception $e) {
            error_log("WebSocket notification error: " . $e->getMessage());
        }
    }
}
