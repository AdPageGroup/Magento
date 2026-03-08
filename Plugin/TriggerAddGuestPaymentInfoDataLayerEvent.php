<?php declare(strict_types=1);

namespace Tagging\GTM\Plugin;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\AddPaymentInfo;

class TriggerAddGuestPaymentInfoDataLayerEvent
{
    public function __construct(private readonly CheckoutSessionDataProviderInterface $checkoutSessionDataProvider, private readonly AddPaymentInfo $addPaymentInfo, private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId)
    {
    }

    public function afterSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $orderId,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ) {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        $addPaymentInfoEventData = $this->addPaymentInfo
            ->setPaymentMethod($paymentMethod->getMethod())
            ->setCartId((int)$cartId)
            ->get();
        $this->checkoutSessionDataProvider->add('add_payment_info_event', $addPaymentInfoEventData);

        return $orderId;
    }

    public function afterSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $orderId,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ) {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        $addPaymentInfoEventData = $this->addPaymentInfo
            ->setPaymentMethod($paymentMethod->getMethod())
            ->setCartId((int)$cartId)
            ->get();
        $this->checkoutSessionDataProvider->add('add_payment_info_event', $addPaymentInfoEventData);

        return $orderId;
    }
}
