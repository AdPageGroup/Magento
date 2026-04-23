<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\EnhancedConversions;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Tagging\GTM\Api\Data\TagInterface;

class Sha256EmailAddress implements TagInterface
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(private readonly CheckoutSession $checkoutSession, private readonly OrderRepositoryInterface $orderRepository)
    {
    }

    #[\Override]
    public function get(): string
    {
        $order = $this->getOrder();
        return hash('sha256', trim(strtolower($order->getCustomerEmail())));
    }

    /**
     * @return OrderInterface
     */
    private function getOrder(): OrderInterface
    {
        return $this->orderRepository->get($this->checkoutSession->getLastRealOrder()->getId());
    }
}
