<?php
declare(strict_types=1);

namespace Tagging\GTM\SessionDataProvider;

use Magento\Checkout\Model\Session as CheckoutSession;
use Tagging\GTM\Api\CheckoutSessionDataProviderInterface;
use Tagging\GTM\Util\SessionEventStamper;

class CheckoutSessionDataProvider implements CheckoutSessionDataProviderInterface
{
    private CheckoutSession $checkoutSession;
    private SessionEventStamper $sessionEventStamper;

    public function __construct(
        CheckoutSession $checkoutSession,
        SessionEventStamper $sessionEventStamper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->sessionEventStamper = $sessionEventStamper;
    }
    
    public function add(string $identifier, array $data)
    {
        $gtmData = $this->get();
        $gtmData[$identifier] = $this->sessionEventStamper->stamp($data);
        $this->checkoutSession->setYireoGtmData($gtmData);
    }
    
    public function get(): array
    {
        $gtmData = $this->checkoutSession->getYireoGtmData();
        if (is_array($gtmData)) {
            return $gtmData;
        }
        
        return [];
    }
    
    public function clear()
    {
        $this->checkoutSession->setYireoGtmData([]);
    }
}
