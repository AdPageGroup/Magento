<?php declare(strict_types=1);

namespace Tagging\GTM\Plugin;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\AddShippingInfo;

class TriggerAddShippingInfoDataLayerEvent
{
    public function __construct(private readonly CheckoutSessionDataProviderInterface $checkoutSessionDataProvider, private readonly AddShippingInfo $addShippingInfo)
    {
    }

    /**
     * @param ShippingInformationManagementInterface $subject
     * @param PaymentDetailsInterface $paymentDetails
     * @param ShippingInformationInterface $addressInformation
     * @return PaymentDetailsInterface
     */
    public function afterSaveAddressInformation(
        ShippingInformationManagementInterface $subject,
        PaymentDetailsInterface $paymentDetails,
        mixed $cartId,
        ShippingInformationInterface $addressInformation
    ) {

        $event = $this->addShippingInfo->get();
        if (array_key_exists('event', $event)) {
            $this->checkoutSessionDataProvider->add('add_shipping_info_event', $event);
        }

        return $paymentDetails;
    }
}
