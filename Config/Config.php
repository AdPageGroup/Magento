<?php

declare(strict_types=1);

namespace Tagging\GTM\Config;

use Magento\Cookie\Helper\Cookie as CookieHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Tagging\GTM\DataLayer\Tag\Version;

class Config implements ArgumentInterface
{
    private ScopeConfigInterface $scopeConfig;
    private CookieHelper $cookieHelper;
    private StoreManagerInterface $storeManager;
    private AppState $appState;
    private Version $version;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CookieHelper $cookieHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CookieHelper $cookieHelper,
        AppState $appState,
        Version $version
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->cookieHelper = $cookieHelper;
        $this->appState = $appState;
        $this->version = $version;
    }

    /**
     * Check whether the module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $enabled = (bool)$this->getModuleConfigValue('enabled', false);
        if (false === $enabled) {
            return false;
        }

        return true;
    }

    /**
     * Check if lifetime value calculation is enabled in configuration
     *
     * @return bool
     */
    public function isLifetimeValueEnabled(): bool
    {
        $enabledLifetimeValue = (bool)$this->getModuleConfigValue('lifetime_value', false);
        if (false === $enabledLifetimeValue) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the module should place the GTM code or it is done by the user
     * 
     * @return bool 
     */
    public function isPlacedByPlugin(): bool
    {
        $enabled = (bool)$this->getModuleConfigValue('choose_script_placement', false);
        if (false === $enabled) {
            return true;
        }

        return false;
    }

    /**
     *
     * Get the Google tag manager url. Defaults to googletagmanager.com. when field is filled return that url.
     *
     * @return string
     */
    public function getGoogleTagmanagerUrl(): string
    {
        return $this->getModuleConfigValue(
            'serverside_gtm_url',
            ''
        );
    }

    /**
     * Check whether the module is in debugging mode
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool)$this->getModuleConfigValue('debug');
    }

    /**
     * Return the GA ID
     *
     * @return string
     */
    public function getConfig(): string
    {
        return (string)$this->getModuleConfigValue('config');
    }

    /**
     * @return string[]
     */
    public function getCustomerEavAttributeCodes(): array
    {
        return [
            'email',
            'firstname',
            'middlename',
            'lastname',
            'dob',
            'created_at'
        ];
    }

    /**
     * @return string
     */
    public function getStoreName(): string
    {
        $storeName = (string)$this->getConfigValue('general/store_information/name');
        if (!empty($storeName)) {
            return $storeName;
        }

        return (string)$this->storeManager->getDefaultStoreView()->getName();
    }

    public function getStoreDomain(): string
    {
        return (string)$this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Return a configuration value
     *
     * @param string $key
     * @param null $defaultValue
     *
     * @return mixed|null
     */
    public function getModuleConfigValue(string $key, $defaultValue = null)
    {
        return $this->getConfigValue('GTM/settings/' . $key, $defaultValue);
    }

    public function getModuleConfigValueAdvanced(string $key, $defaultValue = null)
    {
        return $this->getConfigValue('GTM/advanced/' . $key, $defaultValue);
    }

    /**
     * Return a configuration value
     *
     * @param string $key
     * @param null $defaultValue
     *
     * @return mixed|null
     */
    public function getConfigValue(string $key, $defaultValue = null)
    {
        try {
            $value = $this->scopeConfig->getValue(
                $key,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()
            );
        } catch (NoSuchEntityException $e) {
            return $defaultValue;
        }

        if (empty($value)) {
            $value = $defaultValue;
        }

        return $value;
    }

    public function getVersion(): string
    {
        return $this->version->get();
    }

    /**
     * Get webhook trigger mode configuration
     *
     * @return string
     */
    public function getWebhookTriggerMode(): string
    {
        return (string)$this->getModuleConfigValueAdvanced('webhook_trigger_mode', 'default');
    }

    /**
     * Get webhook trigger order state configuration
     *
     * @return string
     */
    public function getWebhookTriggerOrderState(): string
    {
        return (string)$this->getModuleConfigValueAdvanced('webhook_trigger_order_state', '');
    }

    /**
     * Check if webhook should be triggered based on order state
     *
     * @return bool
     */
    public function isWebhookTriggerOnOrderState(): bool
    {
        return $this->getWebhookTriggerMode() === 'on_order_state';
    }

    /**
     * Check if page-specific checking is enabled for purchase event
     *
     * @return bool
     */
    public function isPurchaseEventPageCheckEnabled(): bool
    {
        return (bool)$this->getModuleConfigValueAdvanced('purchase_event_page_check_enabled', true);
    }

    /**
     * Get allowed checkout actions for purchase event
     *
     * @return array
     */
    public function getPurchaseEventAllowedCheckoutActions(): array
    {
        // Only return actions if page check is enabled
        if (!$this->isPurchaseEventPageCheckEnabled()) {
            return [];
        }

        $configValue = $this->getModuleConfigValueAdvanced('purchase_event_allowed_checkout_actions', null);
        
        // If null or empty and page check is enabled, return all available actions as default
        if ($configValue === null || $configValue === '' || $configValue === false) {
            return $this->getAllAvailableCheckoutActions();
        }
        
        // Multiselect values in Magento can be stored in different formats:
        // 1. As an array (when retrieved via ScopeConfigInterface in some cases)
        // 2. As a comma-separated string (most common)
        // 3. As a JSON string (less common)
        
        if (is_array($configValue)) {
            $actions = array_filter(array_map('trim', $configValue));
            // If array is empty, return all available actions
            return empty($actions) ? $this->getAllAvailableCheckoutActions() : $actions;
        }
        
        if (is_string($configValue)) {
            // Try JSON decode first (in case it's stored as JSON)
            $decoded = json_decode($configValue, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $actions = array_filter(array_map('trim', $decoded));
                return empty($actions) ? $this->getAllAvailableCheckoutActions() : $actions;
            }
            
            // Otherwise treat as comma-separated string
            $actions = array_filter(array_map('trim', explode(',', $configValue)));
            return empty($actions) ? $this->getAllAvailableCheckoutActions() : $actions;
        }
        
        return $this->getAllAvailableCheckoutActions();
    }

    /**
     * Get all available checkout actions
     *
     * @return array
     */
    private function getAllAvailableCheckoutActions(): array
    {
        return [
            'checkout_index_index',
            'checkout_onepage_success',
            'checkout_cart_index',
            'checkout_onepage_index',
            'checkout_multishipping_index',
            'checkout_multishipping_success',
        ];
    }

    /**
     * @return bool
     */
    private function isDeveloperMode(): bool
    {
        return $this->appState->getMode() === AppState::MODE_DEVELOPER;
    }
}
