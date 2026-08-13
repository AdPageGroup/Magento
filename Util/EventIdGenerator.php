<?php declare(strict_types=1);

namespace Tagging\GTM\Util;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Builds the deterministic event identifier that ties a browser purchase event to
 * the server-side order_created webhook.
 *
 * Meta deduplicates browser and Conversions API events on event_id, not on
 * transaction_id, so both sides have to produce the exact same value. Keep this
 * the only place where the format is defined.
 */
class EventIdGenerator
{
    /**
     * @param OrderInterface $order
     * @return string
     */
    public function forOrder(OrderInterface $order): string
    {
        return 'purchase.' . (string)$order->getIncrementId();
    }
}
