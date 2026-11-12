<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as Cart;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Tag\Cart\CartItems;
use Tagging\GTM\Util\PriceFormatter;
use Tagging\GTM\Util\PriceVisibility;

class AddPaymentInfo implements EventInterface
{
    private CartItems $cartItems;
    private CartRepositoryInterface $cartRepository;
    private PriceFormatter $priceFormatter;
    private PriceVisibility $priceVisibility;
    private int $cartId;
    private string $paymentMethod;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param CartItems $cartItems
     * @param PriceFormatter $priceFormatter
     * @param PriceVisibility $priceVisibility
     */
    public function __construct(
        CartRepositoryInterface  $cartRepository,
        CartItems $cartItems,
        PriceFormatter $priceFormatter,
        PriceVisibility $priceVisibility
    ) {
        $this->cartItems = $cartItems;
        $this->cartRepository = $cartRepository;
        $this->priceFormatter = $priceFormatter;
        $this->priceVisibility = $priceVisibility;
    }

    /**
     * @return string[]
     */
    public function get(): array
    {
        /** @var Cart $cart */
        $cart = $this->cartRepository->get($this->cartId);
        $value = $this->priceVisibility->filter(
            $this->priceFormatter->format((float)$cart->getGrandTotal())
        );

        $eventData = [
            'event' => 'trytagging_add_payment_info',
            'ecommerce' => [
                'currency' => $cart->getQuoteCurrencyCode(),
                'value' => $value,
                'coupon' => $cart->getCouponCode(),
                'payment_type' => $this->paymentMethod,
                'items' => $this->cartItems->get()
            ]
        ];

        if ($value === null) {
            unset($eventData['ecommerce']['value']);
        }

        return $eventData;
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
