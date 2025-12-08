<?php

declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Tagging\GTM\Api\Data\TagInterface;

class UserData implements TagInterface
{
    private CheckoutSession $checkoutSession;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        $order = $this->getOrder();
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
     * @return OrderInterface
     */
    private function getOrder(): OrderInterface
    {
        return $this->orderRepository->get($this->checkoutSession->getLastRealOrder()->getId());
    }
}
