<?php declare(strict_types=1);

namespace Tagging\GTM\Util;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Model\Config\Source\HidePriceMode;

/**
 * Decides whether prices may be exposed in the dataLayer for the current visitor.
 *
 * B2B stores that only show prices to logged in (or approved) customers must not leak those prices
 * through the dataLayer either: everything that ends up in the dataLayer is readable in the page
 * source and in the browser console. This only guards the storefront. Server side contexts - the
 * order_created webhook, the admin panel and the REST API - have no customer session and always keep
 * the real amounts, so revenue reporting stays intact.
 */
class PriceVisibility
{
    private Config $config;
    private CustomerSession $customerSession;
    private AppState $appState;

    /**
     * @param Config $config
     * @param CustomerSession $customerSession
     * @param AppState $appState
     */
    public function __construct(
        Config $config,
        CustomerSession $customerSession,
        AppState $appState
    ) {
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->appState = $appState;
    }

    /**
     * Whether prices may be added to the dataLayer for the current visitor
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        if (false === $this->config->isHidePricesEnabled()) {
            return true;
        }

        if (false === $this->isFrontend()) {
            return true;
        }

        $hiddenForCustomerGroups = $this->config->getHidePricesCustomerGroups();
        if (empty($hiddenForCustomerGroups)) {
            return true;
        }

        return !in_array($this->getCustomerGroupId(), $hiddenForCustomerGroups, true);
    }

    /**
     * Whether a hidden price should be left out of the dataLayer instead of being sent as 0
     *
     * @return bool
     */
    public function isRemoveMode(): bool
    {
        return $this->config->getHidePricesMode() === HidePriceMode::MODE_REMOVE;
    }

    /**
     * Return the price to expose, or null when the key should be left out of the dataLayer entirely
     *
     * @param float|null $price
     * @return float|null
     */
    public function filter(?float $price): ?float
    {
        if ($this->isAllowed()) {
            return $price;
        }

        return $this->isRemoveMode() ? null : 0.0;
    }

    /**
     * @return int
     */
    private function getCustomerGroupId(): int
    {
        return (int)$this->customerSession->getCustomerGroupId();
    }

    /**
     * @return bool
     */
    private function isFrontend(): bool
    {
        try {
            return $this->appState->getAreaCode() === Area::AREA_FRONTEND;
        } catch (LocalizedException $localizedException) {
            return false;
        }
    }
}
