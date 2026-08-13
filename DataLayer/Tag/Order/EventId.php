<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Util\EventIdGenerator;

class EventId implements TagInterface
{
    private CheckoutSession $checkoutSession;
    private EventIdGenerator $eventIdGenerator;

    /**
     * @param CheckoutSession $checkoutSession
     * @param EventIdGenerator $eventIdGenerator
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        EventIdGenerator $eventIdGenerator
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->eventIdGenerator = $eventIdGenerator;
    }

    /**
     * @return string
     */
    public function get(): string
    {
        // The increment ID is already on the session order, so there is no need
        // to load the order again through the repository
        return $this->eventIdGenerator->forOrder($this->checkoutSession->getLastRealOrder());
    }
}
