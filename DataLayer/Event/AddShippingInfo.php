<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Tag\Cart\CartItems;

class AddShippingInfo implements EventInterface
{
    /**
     * @param CartItems $cartItems
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(private readonly CartItems $cartItems, private readonly ShippingMethodManagementInterface $shippingMethodManagement, private readonly CheckoutSession $checkoutSession)
    {
    }

    /**
     * @return string[]
     */
    #[\Override]
    public function get(): array
    {
        if (false === $this->checkoutSession->hasQuote()) {
            return [];
        }

        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException) {
            return [];
        }

        $shippingMethod = $this->getShippingMethodFromQuote($quote);
        if (empty($shippingMethod)) {
            return [];
        }

        return [
            'event' => 'trytagging_add_shipping_info',
            'ecommerce' => [
                'shipping_tier' => $shippingMethod,
                'items' => $this->cartItems->get(),
            ],
        ];
    }

    /**
     * @param CartInterface $quote
     * @return string|null
     */
    public function getShippingMethodFromQuote(CartInterface $quote): ?string
    {
        try {
            // @phpstan-ignore-next-line
            $shippingMethod = $this->shippingMethodManagement->get($quote->getId());
            if ($shippingMethod instanceof ShippingMethodInterface) {
                return $shippingMethod->getCarrierCode().'_'.$shippingMethod->getMethodCode();
            }
        } catch (NoSuchEntityException|StateException) {
        }

        try {
            // @phpstan-ignore-next-line
            return $quote->getShippingAddress()->getShippingMethod();
        } catch (NoSuchEntityException) {
        }

        return null;
    }
}
