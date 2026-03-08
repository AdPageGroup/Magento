<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag;

use Magento\Framework\App\State;
use Tagging\GTM\Api\Data\TagInterface;

class LiveOnly implements TagInterface
{
    /**
     * @param State $state
     */
    public function __construct(private readonly State $state)
    {
    }

    #[\Override]
    public function get(): bool
    {
        return $this->state->getMode() === State::MODE_PRODUCTION;
    }
}
