<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Tagging\GTM\Api\Data\MergeTagInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Util\PriceFormatter;

class Order implements MergeTagInterface
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Config $config
     */
    public function __construct(private readonly CheckoutSession $checkoutSession, private readonly OrderRepositoryInterface $orderRepository, private readonly Config $config, private readonly PriceFormatter $priceFormatter)
    {
    }

    /**
     * @return array
     */
    #[\Override]
    public function merge(): array
    {
        $order = $this->getOrder();
        return [
            'currency' => (string)$order->getOrderCurrencyCode(),
            'value' => $this->priceFormatter->format((float)$order->getGrandTotal()),
            'tax' => $this->priceFormatter->format((float)$order->getTaxAmount()),
            'shipping' => $this->priceFormatter->format((float)$order->getShippingInclTax()),
            'affiliation' => $this->config->getStoreName(),
            'transaction_id' => $order->getIncrementId(),
            'coupon' => $order->getCouponCode()
        ];
    }

    /**
     * @return OrderInterface
     */
    private function getOrder(): OrderInterface
    {
        return $this->orderRepository->get($this->checkoutSession->getLastRealOrder()->getId());
    }
}
