<?php

declare(strict_types=1);

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
            'user_data' => $this->getUserData($order)
        ];
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function getUserData(OrderInterface $order): array
    {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        return [
            'customer_id' => $order->getCustomerId() ?? '',
            'billing_first_name' => $billingAddress ? $billingAddress->getFirstname() ?? '' : '',
            'billing_last_name' => $billingAddress ? $billingAddress->getLastname() ?? '' : '',
            'billing_address' => $billingAddress && $billingAddress->getStreet() ? $billingAddress->getStreet()[0] ?? '' : '',
            'billing_postcode' => $billingAddress ? $billingAddress->getPostcode() ?? '' : '',
            'billing_country' => $billingAddress ? $billingAddress->getCountryId() ?? '' : '',
            'billing_state' => $billingAddress ? $billingAddress->getRegion() ?? '' : '',
            'billing_city' => $billingAddress ? $billingAddress->getCity() ?? '' : '',
            'billing_email' => $billingAddress ? $billingAddress->getEmail() ?? '' : '',
            'billing_phone' => $billingAddress ? $billingAddress->getTelephone() ?? '' : '',
            'shipping_first_name' => $shippingAddress ? $shippingAddress->getFirstname() ?? '' : '',
            'shipping_last_name' => $shippingAddress ? $shippingAddress->getLastname() ?? '' : '',
            'shipping_company' => $shippingAddress ? $shippingAddress->getCompany() ?? '' : '',
            'shipping_address' => $shippingAddress && $shippingAddress->getStreet() ? $shippingAddress->getStreet()[0] ?? '' : '',
            'shipping_postcode' => $shippingAddress ? $shippingAddress->getPostcode() ?? '' : '',
            'shipping_country' => $shippingAddress ? $shippingAddress->getCountryId() ?? '' : '',
            'shipping_state' => $shippingAddress ? $shippingAddress->getRegion() ?? '' : '',
            'shipping_city' => $shippingAddress ? $shippingAddress->getCity() ?? '' : '',
            'shipping_phone' => $shippingAddress ? $shippingAddress->getTelephone() ?? '' : '',
            'email' => $order->getCustomerEmail() ?? '',
            'first_name' => $order->getCustomerFirstname() ?? '',
            'last_name' => $order->getCustomerLastname() ?? '',
            'new_customer' => $order->getCustomerIsGuest() ? 'true' : 'false'
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
