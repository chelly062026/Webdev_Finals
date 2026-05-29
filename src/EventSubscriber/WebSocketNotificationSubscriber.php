<?php

namespace App\EventSubscriber;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\StockLog;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebSocketNotificationSubscriber implements EventSubscriberInterface
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
        $this->handleEvent($args, 'created');
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args, 'updated');
    }

    private function handleEvent(LifecycleEventArgs $args, string $action): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Order) {
            $this->sendNotification('order.' . $action, [
                'orderId' => $entity->getId(),
                'status' => $entity->getStatus()
            ]);
        } elseif ($entity instanceof StockLog) {
            $this->sendNotification('stock.updated', [
                'logId' => $entity->getId(),
                'productId' => $entity->getProduct() ? $entity->getProduct()->getId() : null
            ]);
        } elseif ($entity instanceof Product) {
            $this->sendNotification('stock.updated', [
                'productId' => $entity->getId(),
                'stock' => $entity->getStock()
            ]);
        }
    }

    private function sendNotification(string $type, array $data): void
    {
        try {
            $this->httpClient->request('POST', $this->websocketUrl, [
                'json' => [
                    'type' => $type,
                    'data' => $data,
                    'title' => 'System Update',
                    'body' => 'A change occurred in ' . $type
                ],
            ]);
            
            error_log("WebSocket notification sent for " . $type);
        } catch (\Exception $e) {
            error_log("WebSocket notification error: " . $e->getMessage());
        }
    }
}
