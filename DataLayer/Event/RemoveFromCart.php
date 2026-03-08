<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Quote\Api\Data\CartItemInterface;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Mapper\CartItemDataMapper;

class RemoveFromCart implements EventInterface
{
    private CartItemInterface $cartItem;

    /**
     * @param CartItemDataMapper $cartItemDataMapper
     */
    public function __construct(private readonly CartItemDataMapper $cartItemDataMapper)
    {
    }

    /**
     * @return array
     */
    #[\Override]
    public function get(): array
    {
        $cartItemData = $this->cartItemDataMapper->mapByCartItem($this->cartItem);
        return [
            'event' => 'trytagging_remove_from_cart',
            'ecommerce' => [
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
