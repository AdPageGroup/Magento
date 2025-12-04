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

        // Get page information
        $fullActionName = $this->request->getFullActionName();
        $requestUri = $this->request->getRequestUri();
        $moduleName = $this->request->getModuleName();
        $controllerName = $this->request->getControllerName();
        $actionName = $this->request->getActionName();

        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Purchase event triggered');
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): page_full_action_name: ' . $fullActionName);
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): page_module: ' . $moduleName);
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): page_controller: ' . $controllerName);
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): page_action: ' . $actionName);
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): page_request_uri: ' . $requestUri);
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): order_id: ' . $order->getId());
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): order_increment_id: ' . $order->getIncrementId());
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): order_status: ' . $order->getStatus());
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): order_state: ' . $order->getState());
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): order_grand_total: ' . $order->getGrandTotal());
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): order_total_paid: ' . $order->getTotalPaid());
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): order_total_due: ' . $order->getTotalDue());

        // Check if page-specific checking is enabled
        $pageCheckEnabled = $this->config->isPurchaseEventPageCheckEnabled();
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): page_check_enabled: ' . ($pageCheckEnabled ? 'true' : 'false'));

        // If page check is enabled, validate we're on an allowed checkout page
        if ($pageCheckEnabled) {
            if (!$this->isCheckoutPage()) {
                $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Not on an allowed checkout page, skipping purchase event');
                return;
            }
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): On allowed checkout page, proceeding with purchase event');
        } else {
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Page check disabled - purchase event will fire from any page');
        }

        // Check if payment check is enabled
        $paymentCheckEnabled = $this->config->isPurchaseEventPaymentCheckEnabled();
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): payment_check_enabled: ' . ($paymentCheckEnabled ? 'true' : 'false'));

        if ($paymentCheckEnabled) {
            if (!$this->shouldTriggerPurchaseEvent($order)) {
                $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Payment check failed - order is not fully paid, skipping purchase event');
                return;
            }
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Payment check passed - order is fully paid, sending purchase event');
        } else {
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Payment check disabled - sending purchase event without payment verification');
        }

        $this->checkoutSessionDataProvider->add(
            'purchase_event',
            $this->purchaseEvent->setOrder($order)->get()
        );
        
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::execute(): Purchase event added to checkout session');
    }

    /**
     * Check if purchase event should be triggered based on payment status
     *
     * @param OrderInterface $order
     * @return bool
     */
    private function shouldTriggerPurchaseEvent(OrderInterface $order): bool
    {
        $grandTotal = (float)$order->getGrandTotal();
        $totalPaid = (float)$order->getTotalPaid();
        $tolerance = 0.01; // Tolerance for floating point comparison

        $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): grand_total: ' . $grandTotal);
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): total_paid: ' . $totalPaid);
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): difference: ' . abs($grandTotal - $totalPaid));
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): tolerance: ' . $tolerance);

        // Check if order is fully paid (with tolerance)
        $isFullyPaid = abs($grandTotal - $totalPaid) <= $tolerance;
        
        if ($isFullyPaid) {
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): Order is fully paid');
            
            // Additional check: order should be in a paid state
            $paidStates = ['processing', 'complete'];
            $orderState = $order->getState();
            $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): order_state: ' . $orderState);
            
            if (in_array($orderState, $paidStates)) {
                $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): Order state is in paid states, purchase event will be sent');
                return true;
            } else {
                $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): Order state is not in paid states, but order is fully paid - purchase event will be sent');
                return true;
            }
        }

        $this->debugger->debug('TriggerPurchaseDataLayerEvent::shouldTriggerPurchaseEvent(): Order is not fully paid, purchase event will not be sent');
        return false;
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
        
        // Get allowed checkout actions from configuration
        // This will return all available actions if none are configured (default behavior)
        $allowedActions = $this->config->getPurchaseEventAllowedCheckoutActions();
        
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::isCheckoutPage(): Allowed actions: ' . implode(', ', $allowedActions));
        $this->debugger->debug('TriggerPurchaseDataLayerEvent::isCheckoutPage(): Current action: ' . $fullActionName);
        
        return in_array($fullActionName, $allowedActions, true);
    }
}
