<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Tagging\GTM\Api\Data\EventInterface;

class Logout implements EventInterface
{
    #[\Override]
    public function get(): array
    {
        return [
            'event' => 'trytagging_logout'
        ];
    }
}
