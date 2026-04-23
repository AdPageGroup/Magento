<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as Cart;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Tag\Cart\CartItems;
use Tagging\GTM\Util\PriceFormatter;

class AddPaymentInfo implements EventInterface
{
    private int $cartId;
    private string $paymentMethod;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param CartItems $cartItems
     */
    public function __construct(private readonly CartRepositoryInterface  $cartRepository, private readonly CartItems $cartItems, private readonly PriceFormatter $priceFormatter)
    {
    }

    /**
     * @return string[]
     */
    #[\Override]
    public function get(): array
    {
        /** @var Cart $cart */
        $cart = $this->cartRepository->get($this->cartId);
        return [
            'event' => 'trytagging_add_payment_info',
            'ecommerce' => [
                'currency' => $cart->getQuoteCurrencyCode(),
                'value' => $this->priceFormatter->format((float)$cart->getGrandTotal()),
                'coupon' => $cart->getCouponCode(),
                'payment_type' => $this->paymentMethod,
                'items' => $this->cartItems->get()
            ]
        ];
    }

    /**
     * @param string $paymentMethod
     * @return AddPaymentInfo
     */
    public function setPaymentMethod(string $paymentMethod): AddPaymentInfo
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @param int $cartId
     * @return AddPaymentInfo
     */
    public function setCartId(int $cartId): AddPaymentInfo
    {
        $this->cartId = $cartId;
        return $this;
    }
}
