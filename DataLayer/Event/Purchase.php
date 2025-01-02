<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Sales\Api\Data\OrderInterface;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\DataLayer\Tag\Order\OrderItems;
use Tagging\GTM\Util\PriceFormatter;

class Purchase implements EventInterface
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
            'event' => 'trytagging_purchase',
            'ecommerce' => [
                'transaction_id' => $order->getIncrementId(),
                'affiliation' => $this->config->getStoreName(),
                'currency' => $order->getOrderCurrencyCode(),
                'value' => $this->priceFormatter->format((float)$order->getGrandTotal()),
                'tax' => $this->priceFormatter->format((float)$order->getTaxAmount()),
                'shipping' => $this->priceFormatter->format((float)$order->getShippingInclTax()),
                'coupon' => $order->getCouponCode(),
                'items' => $this->orderItems->setOrder($order)->get()
            ],
            'user_data' => [
                'customer_id' => $order->getCustomerId() ?? '',
                'customer_email' => $order->getCustomerEmail() ?? '',
                'customer_name' => $order->getCustomerFirstname() ?? '' . ' ' . $order->getCustomerLastname() ?? '',
                'customer_phone' => $order->getCustomerTelephone() ?? '',
                'customer_address' => $order->getCustomerAddress() ?? '',
                'customer_city' => $order->getCustomerCity() ?? '',
                'customer_state' => $order->getCustomerState() ?? '',
                'customer_zip' => $order->getCustomerPostcode() ?? '',
                'customer_country' => $order->getCustomerCountry() ?? '',
            ]
        ];
    }

    /**
     * @param OrderInterface $order
     * @return Purchase
     */
    public function setOrder(OrderInterface $order): Purchase
    {
        $this->order = $order;
        return $this;
    }
}
