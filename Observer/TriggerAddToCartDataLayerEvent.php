<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\AddToCart as AddToCartEvent;

class TriggerAddToCartDataLayerEvent implements ObserverInterface
{
    public function __construct(private readonly CheckoutSessionDataProviderInterface $checkoutSessionDataProvider, private readonly AddToCartEvent $addToCartEvent)
    {
    }

    #[\Override]
    public function execute(Observer $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getData('product');
        $qty = (int)$observer->getData('request')->getParam('qty');
        if ($qty === 0) {
            $qty = 1;
        }

        $this->checkoutSessionDataProvider->add(
            'add_to_cart_event',
            $this->addToCartEvent->setProduct($product)->setQty($qty)->get()
        );
    }
}
