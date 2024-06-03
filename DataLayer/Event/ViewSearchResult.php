<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Tagging\GTM\Api\Data\EventInterface;

class ViewSearchResult implements EventInterface
{
    private ?string $searchTerm = null;

    public function get(): array
    {
        return [
            'event' => 'trytagging_view_search_result',
            'search_term' => $this->searchTerm,
        ];
    }

    public function setSearchTerm(?string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }
}
