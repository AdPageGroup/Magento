<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Quote\Model\Quote;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Tag\Cart\CartItems;
use Tagging\GTM\DataLayer\Tag\Cart\CartValue;
use Tagging\GTM\DataLayer\Tag\CurrencyCode;

class BeginCheckout implements EventInterface
{
    /**
     * @param Quote $quote
     * @param CartItems $cartItems
     * @param CartValue $cartValue
     * @param CurrencyCode $currencyCode
     */
    public function __construct(private readonly Quote $quote, private readonly CartItems $cartItems, private readonly CartValue $cartValue, private readonly CurrencyCode $currencyCode)
    {
    }

    #[\Override]
    public function get(): array
    {
        return [
            'event' => 'trytagging_begin_checkout',
            'ecommerce' => [
                'currency' => $this->currencyCode->get(),
                'value' => $this->cartValue->get(),
                'coupon' => $this->quote->getCouponCode(),
                'items' => $this->cartItems->get()
            ]
        ];
    }
}
