<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Tagging\GTM\Api\CustomerSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\Login as LoginEvent;

class TriggerLoginDataLayerEvent implements ObserverInterface
{
    private CustomerSessionDataProviderInterface $customerSessionDataProvider;
    private LoginEvent $loginEvent;

    public function __construct(
        CustomerSessionDataProviderInterface $customerSessionDataProvider,
        LoginEvent $loginEvent
    ) {
        $this->customerSessionDataProvider = $customerSessionDataProvider;
        $this->loginEvent = $loginEvent;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $eventData = $this->loginEvent->setCustomer($customer)->get();
        $this->customerSessionDataProvider->add('login_event', $eventData);
    }
}
