<?php declare(strict_types=1);

namespace Tagging\GTM\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface NewCustomerResolverInterface
{
    /**
     * Determine whether the given order belongs to a new customer for the
     * purpose of the `new_customer` field in the GTM data layer and webhook.
     *
     * Merchants can override this via a DI preference to replace the default
     * (guest = new customer) heuristic with e.g. a prior-order-count check.
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isNewCustomer(OrderInterface $order): bool;
}
