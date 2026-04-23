<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\DataLayer\Tag\Cart\CartItems;
use Tagging\GTM\DataLayer\Tag\Cart\CartValue;
use Tagging\GTM\DataLayer\Tag\CurrencyCode;

class ViewCart implements EventInterface
{
    /**
     * @param CartItems $cartItems
     * @param CartValue $cartValue
     * @param CurrencyCode $currencyCode
     * @param Config $config
     */
    public function __construct(private readonly CartItems $cartItems, private readonly CartValue $cartValue, private readonly CurrencyCode $currencyCode, private readonly Config $config)
    {
    }

    /**
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function get(): array
    {
        return [
            'meta' => [
                'cacheable' => true,
                'allowed_pages' => $this->getAllowedPages(),
                'allowed_events' => $this->getAllowedEvents(),
            ],
            'event' => 'trytagging_view_cart',
            'ecommerce' => [
                'currency' => $this->currencyCode->get(),
                'value' => $this->cartValue->get(),
                'items' => $this->cartItems->get()
            ]
        ];
    }

    /**
     * @return string[]
     */
    private function getAllowedPages(): array
    {
        return ['/checkout/cart/'];
    }

    /**
     * @return string[]
     */
    private function getAllowedEvents(): array
    {
        return [];
    }
}
