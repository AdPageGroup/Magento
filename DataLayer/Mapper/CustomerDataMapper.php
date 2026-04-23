<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Mapper;

use Magento\Customer\Api\Data\CustomerInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Util\Attribute\GetAttributeValue;
use Tagging\GTM\Util\CamelCase;

class CustomerDataMapper
{
    /**
     * @param CamelCase $camelCase
     * @param Config $config
     * @param GetAttributeValue $getAttributeValue
     */
    public function __construct(private readonly CamelCase $camelCase, private readonly Config $config, private readonly GetAttributeValue $getAttributeValue)
    {
    }

    /**
     * @param CustomerInterface $customer
     * @param string $prefix
     * @return array
     */
    public function mapByCustomer(CustomerInterface $customer, string $prefix = ''): array
    {
        $customerData = [];
        $customerFields = $this->getCustomerFields();
        foreach ($customerFields as $customerAttributeCode) {
            $dataLayerKey = lcfirst($prefix . $this->camelCase->to($customerAttributeCode));
            $attributeValue = $this->getAttributeValue->getCustomerAttributeValue($customer, $customerAttributeCode);

            if (empty($attributeValue)) {
                continue;
            }

            $customerData[$dataLayerKey] = $attributeValue;
        }

        return $customerData;
    }

    /**
     * @return string[]
     */
    private function getCustomerFields(): array
    {
        return array_filter(array_merge(['id'], $this->config->getCustomerEavAttributeCodes()));
    }
}
