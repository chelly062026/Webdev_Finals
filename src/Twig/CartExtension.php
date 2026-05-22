<?php

namespace App\Twig;

use App\Security\Voter\CustomerVoter;
use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CartExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly Security $security,
    ) {
    }

    public function getGlobals(): array
    {
        $count = 0;
        if ($this->security->isGranted(CustomerVoter::IS_CUSTOMER)) {
            $count = $this->cartService->getTotalQuantity();
        }

        return [
            'cart_count' => $count,
            'is_customer' => $this->security->isGranted(CustomerVoter::IS_CUSTOMER),
        ];
    }
}
