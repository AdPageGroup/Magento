<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Tagging\GTM\Api\CustomerSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\SignUp as SignUpEvent;

class TriggerSignUpDataLayerEvent implements ObserverInterface
{
    private CustomerSessionDataProviderInterface $customerSessionDataProvider;
    private SignUpEvent $signUpEvent;

    public function __construct(
        CustomerSessionDataProviderInterface $customerSessionDataProvider,
        SignUpEvent $signUpEvent
    ) {
        $this->customerSessionDataProvider = $customerSessionDataProvider;
        $this->signUpEvent = $signUpEvent;
    }

    public function execute(Observer $observer)
    {
        $eventData = $this->signUpEvent->get();
        $this->customerSessionDataProvider->add('sign_up_event', $eventData);
    }
}
