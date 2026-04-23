<?php declare(strict_types=1);
/**
 * GoogleTagManager2 plugin for Magento
 *
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2019 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Tagging\GTM\Plugin;

use Magento\Checkout\CustomerData\Cart as CustomerData;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Tagging\GTM\DataLayer\Event\ViewCart as ViewCartEvent;
use Tagging\GTM\DataLayer\Tag\Cart\CartItems;
use Tagging\GTM\SessionDataProvider\CheckoutSessionDataProvider;

class AddDataToCartSection
{
    /**
     * @param CheckoutCart $checkoutCart
     * @param CheckoutSessionDataProvider $checkoutSessionDataProvider
     * @param ViewCartEvent $viewCartEvent
     */
    public function __construct(private readonly CheckoutCart $checkoutCart, private readonly CheckoutSessionDataProvider $checkoutSessionDataProvider, private readonly ViewCartEvent $viewCartEvent)
    {
    }

    /**
     * @param CustomerData $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerData $subject, $result)
    {
        $quoteId = $this->checkoutCart->getQuote()->getId();
        if (empty($result) || !is_array($result) || empty($quoteId)) {
            return $result;
        }

        $gtmData = [];

        $gtmEvents = $this->checkoutSessionDataProvider->get();
        $gtmEvents['view_cart_event'] = $this->viewCartEvent->get();
        $this->checkoutSessionDataProvider->clear();

        return array_merge($result, ['gtm' => $gtmData, 'gtm_events' => $gtmEvents]);
    }
}
