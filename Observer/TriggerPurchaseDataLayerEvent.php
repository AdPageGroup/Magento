<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\Purchase as PurchaseEvent;
use Tagging\GTM\Logger\Debugger;
use Exception;

class TriggerPurchaseDataLayerEvent implements ObserverInterface
{
    public function __construct(private readonly CheckoutSessionDataProviderInterface $checkoutSessionDataProvider, private readonly PurchaseEvent $purchaseEvent, private readonly Debugger $debugger)
    {
    }

    #[\Override]
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        $this->debugger->debug("TriggerPurchaseDataLayerEvent::execute(): has changed ");
        $this->checkoutSessionDataProvider->add(
            'purchase_event',
            $this->purchaseEvent->setOrder($order)->get()
        );
    }
}
