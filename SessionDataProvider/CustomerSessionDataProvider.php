<?php declare(strict_types=1);

namespace Tagging\GTM\SessionDataProvider;

use Magento\Customer\Model\Session as CustomerSession;
use Tagging\GTM\Api\CustomerSessionDataProviderInterface;
use Tagging\GTM\Logger\Debugger;

class CustomerSessionDataProvider implements CustomerSessionDataProviderInterface
{
    public function __construct(private readonly CustomerSession $customerSession, private readonly Debugger $debugger)
    {
    }

    #[\Override]
    public function add(string $identifier, array $data)
    {
        $gtmData = $this->get();
        $gtmData[$identifier] = $data;
        $this->debugger->debug('CustomerSessionDataProvider::add(): ' . $identifier, $data);
        $this->customerSession->setYireoGtmData($gtmData);
    }

    #[\Override]
    public function get(): array
    {
        $gtmData = $this->customerSession->getYireoGtmData();
        if (is_array($gtmData)) {
            return $gtmData;
        }

        return [];
    }

    #[\Override]
    public function clear()
    {
        $this->customerSession->setYireoGtmData([]);
    }
}
