<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\RemoveFromCart as RemoveFromCartEvent;

class TriggerRemoveFromCartDataLayerEvent implements ObserverInterface
{
    public function __construct(private readonly CheckoutSessionDataProviderInterface $checkoutSessionDataProvider, private readonly RemoveFromCartEvent $removeFromCartEvent)
    {
    }

    #[\Override]
    public function execute(Observer $observer)
    {
        /** @var CartItemInterface $quoteItem */
        $quoteItem = $observer->getData('quote_item');
        $this->checkoutSessionDataProvider->add(
            'remove_from_cart_event',
            $this->removeFromCartEvent->setCartItem($quoteItem)->get()
        );
    }
}
