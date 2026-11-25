<?php declare(strict_types=1);

namespace Tagging\GTM\SessionDataProvider;

use Magento\Customer\Model\Session as CustomerSession;
use Tagging\GTM\Api\CustomerSessionDataProviderInterface;
use Tagging\GTM\Logger\Debugger;
use Tagging\GTM\Util\SessionEventStamper;

class CustomerSessionDataProvider implements CustomerSessionDataProviderInterface
{
    private CustomerSession $customerSession;
    private Debugger $debugger;
    private SessionEventStamper $sessionEventStamper;

    public function __construct(
        CustomerSession $customerSession,
        Debugger $debugger,
        SessionEventStamper $sessionEventStamper
    ) {
        $this->customerSession = $customerSession;
        $this->debugger = $debugger;
        $this->sessionEventStamper = $sessionEventStamper;
    }

    public function add(string $identifier, array $data)
    {
        $gtmData = $this->get();
        $gtmData[$identifier] = $this->sessionEventStamper->stamp($data);
        $this->debugger->debug('CustomerSessionDataProvider::add(): ' . $identifier, $data);
        $this->customerSession->setYireoGtmData($gtmData);
    }

    public function get(): array
    {
        $gtmData = $this->customerSession->getYireoGtmData();
        if (is_array($gtmData)) {
            return $gtmData;
        }

        return [];
    }

    public function clear()
    {
        $this->customerSession->setYireoGtmData([]);
    }
}
