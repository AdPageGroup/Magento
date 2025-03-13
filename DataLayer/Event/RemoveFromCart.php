<?php

declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Quote\Api\Data\CartItemInterface;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Tag\CurrencyCode;
use Tagging\GTM\Util\PriceFormatter;
use Tagging\GTM\DataLayer\Mapper\CartItemDataMapper;

class RemoveFromCart implements EventInterface
{
    private CartItemDataMapper $cartItemDataMapper;
    private CartItemInterface $cartItem;
    private CurrencyCode $currencyCode;
    private PriceFormatter $priceFormatter;

    /**
     * @param CartItemDataMapper $cartItemDataMapper
     */
    public function __construct(
        CartItemDataMapper $cartItemDataMapper,
        CurrencyCode $currencyCode,
        PriceFormatter $priceFormatter
    ) {
        $this->cartItemDataMapper = $cartItemDataMapper;
        $this->currencyCode = $currencyCode;
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        $cartItemData = $this->cartItemDataMapper->mapByCartItem($this->cartItem);
        return [
            'event' => 'trytagging_remove_from_cart',
            'ecommerce' => [
                'currency' => $this->currencyCode->get(),
                'value' => $this->priceFormatter->format((float)$cartItemData['price'] * (int)$cartItemData['quantity']),
                'items' => [$cartItemData]
            ]
        ];
    }

    /**
     * @param CartItemInterface $cartItem
     * @return RemoveFromCart
     */
    public function setCartItem(CartItemInterface $cartItem): RemoveFromCart
    {
        $this->cartItem = $cartItem;
        return $this;
    }
}
