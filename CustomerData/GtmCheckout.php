<?php declare(strict_types=1);

namespace Tagging\GTM\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\App\RequestInterface;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\Config\Config;

class GtmCheckout implements SectionSourceInterface
{
    private CheckoutSessionDataProviderInterface $checkoutSessionDataProvider;
    private RequestInterface $request;
    private Config $config;

    /**
     * @param CheckoutSessionDataProviderInterface $checkoutSessionDataProvider
     * @param RequestInterface $request
     * @param Config $config
     */
    public function __construct(
        CheckoutSessionDataProviderInterface $checkoutSessionDataProvider,
        RequestInterface $request,
        Config $config
    ) {
        $this->checkoutSessionDataProvider = $checkoutSessionDataProvider;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getSectionData(): array
    {
        // Add debug logging: the action, module, enabled status
        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] getSectionData called');
        }
        $gtmEvents = $this->checkoutSessionDataProvider->get();

        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] Current GTM events: ' . json_encode($gtmEvents));
        }

        $pageCheckEnabled = $this->config->isPurchaseEventPageCheckEnabled();

        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] PurchaseEventPageCheckEnabled = ' . ($pageCheckEnabled ? 'true' : 'false'));
        }

        if (!$pageCheckEnabled) {
            if (property_exists($this, 'debugger') && $this->debugger) {
                $this->debugger->debug('[GtmCheckout] PageCheck DISABLED, returning all GTM events');
            }
            return ['gtm_events' => $gtmEvents];
        }
        $isCheckoutPage = $this->isCheckoutPage();

        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] isCheckoutPage = ' . ($isCheckoutPage ? 'true' : 'false'));
        }
        
        if ($isCheckoutPage) {
            if (property_exists($this, 'debugger') && $this->debugger) {
                $this->debugger->debug('[GtmCheckout] On checkout page: clearing all checkout session GTM events');
            }
            $this->checkoutSessionDataProvider->clear();
            return ['gtm_events' => $gtmEvents];
        }
        
        $purchaseEvent = $gtmEvents['purchase_event'] ?? null;
        $otherEvents = $gtmEvents;
        unset($otherEvents['purchase_event']);

        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] Not checkout page, will keep only non-purchase_event events: ' . json_encode($otherEvents));
        }

        $this->checkoutSessionDataProvider->clear();

        if ($purchaseEvent !== null) {
            if (property_exists($this, 'debugger') && $this->debugger) {
                $this->debugger->debug('[GtmCheckout] purchase_event found, re-adding it to checkout session data');
            }
            $this->checkoutSessionDataProvider->add('purchase_event', $purchaseEvent);
        }
        
        return ['gtm_events' => $otherEvents];

    }

    private function isCheckoutPage(): bool
    {
        $fullActionName = $this->request->getFullActionName();
        $moduleName = $this->request->getModuleName();

        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] Checking if is checkout page, module=' . $moduleName . ' fullAction=' . $fullActionName);
        }

        if ($moduleName !== 'checkout') {
            if (property_exists($this, 'debugger') && $this->debugger) {
                $this->debugger->debug('[GtmCheckout] Not checkout module, skipping');
            }
            return false;
        }

        $allowedActions = $this->config->getPurchaseEventAllowedCheckoutActions();

        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] Allowed actions: ' . implode(', ', $allowedActions));
            $this->debugger->debug('[GtmCheckout] Current fullActionName: ' . $fullActionName);
        }

        $isAllowed = in_array($fullActionName, $allowedActions, true);

        if (property_exists($this, 'debugger') && $this->debugger) {
            $this->debugger->debug('[GtmCheckout] isCheckoutPage returning: ' . ($isAllowed ? 'true' : 'false'));
        }

        return $isAllowed;
    }
}
