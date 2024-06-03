<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Sales\Api\Data\OrderInterface;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\DataLayer\Tag\Order\OrderItems;
use Tagging\GTM\Util\PriceFormatter;

// @todo: Implement this event
class Refund implements EventInterface
{
    private ?OrderInterface $order = null;
    private OrderItems $orderItems;
    private Config $config;
    private PriceFormatter $priceFormatter;

    public function __construct(
        OrderItems $orderItems,
        Config $config,
        PriceFormatter $priceFormatter
    ) {
        $this->orderItems = $orderItems;
        $this->config = $config;
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * @return string[]
     */
    public function get(): array
    {
        $order = $this->order;
        return [
            'event' => 'trytagging_refund',
            'ecommerce' => [
                'transaction_id' => $order->getIncrementId(),
                'affiliation' => $this->config->getStoreName(),
                'currency' => $order->getOrderCurrencyCode(),
                'value' => $this->priceFormatter->format((float)$order->getGrandTotal()),
                'tax' => $this->priceFormatter->format((float)$order->getTaxAmount()),
                'shipping' => $this->priceFormatter->format((float)$order->getShippingInclTax()),
                'coupon' => $order->getCouponCode(),
                'items' => $this->orderItems->setOrder($order)->get()
            ]
        ];
    }

    /**
     * @param OrderInterface $order
     * @return Refund
     */
    public function setOrder(OrderInterface $order): Refund
    {
        $this->order = $order;
        return $this;
    }
}
