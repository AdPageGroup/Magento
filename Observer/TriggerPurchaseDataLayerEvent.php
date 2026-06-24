<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\DataLayer\Event\Purchase as PurchaseEvent;
use Tagging\GTM\Logger\Debugger;
use Exception;

class TriggerPurchaseDataLayerEvent implements ObserverInterface
{
    private CheckoutSessionDataProviderInterface $checkoutSessionDataProvider;
    private PurchaseEvent $purchaseEvent;
    private Debugger $debugger;
    private Config $config;

    public function __construct(
        CheckoutSessionDataProviderInterface $checkoutSessionDataProvider,
        PurchaseEvent $purchaseEvent,
        Debugger $debugger,
        Config $config
    ) {
        $this->checkoutSessionDataProvider = $checkoutSessionDataProvider;
        $this->purchaseEvent = $purchaseEvent;
        $this->debugger = $debugger;
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if ($this->config->isPurchaseSessionStorageDisabled()) {
            $this->debugger->debug("[TriggerPurchaseDataLayerEvent] Purchase session storage disabled; skipping add");
            return;
        }

        $this->debugger->debug("TriggerPurchaseDataLayerEvent::execute(): has changed ");
        $this->checkoutSessionDataProvider->add(
            'purchase_event',
            $this->purchaseEvent->setOrder($order)->get()
        );
    }
}
