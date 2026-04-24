<?php declare(strict_types=1);

namespace Tagging\GTM\Model\NewCustomerResolver;

use Magento\Sales\Api\Data\OrderInterface;
use Tagging\GTM\Api\NewCustomerResolverInterface;

/**
 * Default resolver: treats guest orders as new customers.
 *
 * Preserves the historical behavior of the module. Override via a DI
 * preference in your own module to implement a different heuristic.
 */
class IsGuestResolver implements NewCustomerResolverInterface
{
    public function isNewCustomer(OrderInterface $order): bool
    {
        return (bool)$order->getCustomerIsGuest();
    }
}
