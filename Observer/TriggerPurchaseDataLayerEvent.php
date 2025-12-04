<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Framework\App\RequestInterface;
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
    private RequestInterface $request;

    public function __construct(
        CheckoutSessionDataProviderInterface $checkoutSessionDataProvider,
        PurchaseEvent $purchaseEvent,
        Debugger $debugger,
        Config $config,
        RequestInterface $request
    ) {
        $this->checkoutSessionDataProvider = $checkoutSessionDataProvider;
        $this->purchaseEvent = $purchaseEvent;
        $this->debugger = $debugger;
        $this->config = $config;
        $this->request = $request;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        $pageCheckEnabled = $this->config->isPurchaseEventPageCheckEnabled();
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): page_check_enabled: ' . ($pageCheckEnabled ? 'true' : 'false'));

        if ($pageCheckEnabled) {
            if (!$this->isCheckoutPage()) {
                $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Not on an allowed checkout page, skipping purchase event');
                return;
            }
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): On allowed checkout page, proceeding with purchase event');
        } else {
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Page check disabled - purchase event will fire from any page');
        }

        $this->checkoutSessionDataProvider->add(
            'purchase_event',
            $this->purchaseEvent->setOrder($order)->get()
        );
    }

    /**
     * Check if the current request is on a checkout page
     *
     * @return bool
     */
    private function isCheckoutPage(): bool
    {
        $fullActionName = $this->request->getFullActionName();
        $moduleName = $this->request->getModuleName();
        
        // Check if we're in the checkout module
        if ($moduleName !== 'checkout') {
            return false;
        }
        
        $allowedActions = $this->config->getPurchaseEventAllowedCheckoutActions();
        
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::isCheckoutPage(): Allowed actions: ' . implode(', ', $allowedActions));
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::isCheckoutPage(): Current action: ' . $fullActionName);
        
        return in_array($fullActionName, $allowedActions, true);
    }
}
