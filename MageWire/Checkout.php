<?php
declare(strict_types=1);

namespace Tagging\GTM\MageWire;

use Magento\Checkout\Model\Session as CheckoutSession;
use Tagging\GTM\DataLayer\Event\AddPaymentInfo;
use Tagging\GTM\DataLayer\Event\AddShippingInfo;
use Tagging\GTM\DataLayer\Event\BeginCheckout;

class Checkout extends Component
{
    public function __construct(private readonly CheckoutSession $checkoutSession, private readonly BeginCheckout $beginCheckout, private readonly AddShippingInfo $addShippingInfo, private readonly AddPaymentInfo $addPaymentInfo)
    {
    }

    public function boot(): void
    {
        $parent = get_parent_class($this);
        if ($parent && method_exists($parent, 'boot')) {
            parent::boot();
        }

        $this->listeners['shipping_method_selected'] = 'triggerShippingMethod';
        $this->listeners['payment_method_selected'] = 'triggerPaymentMethod';
    }

    public function triggerBeginCheckout()
    {
        $this->dispatchBrowserEvent('ga:trigger-event', $this->beginCheckout->get());
    }

    public function triggerShippingMethod()
    {
        $this->dispatchBrowserEvent('ga:trigger-event', $this->addShippingInfo->get());
    }

    public function triggerPaymentMethod()
    {
        $this->addPaymentInfo->setCartId((int) $this->checkoutSession->getQuote()->getId());
        $this->addPaymentInfo->setPaymentMethod((string) $this->checkoutSession->getQuote()->getPayment()->getMethod());
        $this->dispatchBrowserEvent('ga:trigger-event', $this->addPaymentInfo->get());
    }
}
