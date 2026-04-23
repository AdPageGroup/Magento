<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Mapper\CustomerDataMapper;

class Login implements EventInterface
{
    private CustomerInterface $customer;

    public function __construct(private readonly CustomerDataMapper $customerDataMapper)
    {
    }

    public function setCustomer(CustomerInterface $customer): Login
    {
        $this->customer = $customer;
        return $this;
    }

    #[\Override]
    public function get(): array
    {
        return [
            'event' => 'trytagging_login',
            'customer' => $this->customerDataMapper->mapByCustomer($this->customer)
        ];
    }
}
