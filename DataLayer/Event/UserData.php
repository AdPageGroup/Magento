<?php

declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Tag\PageTitle;
use Tagging\GTM\DataLayer\Tag\PageType;
use Tagging\GTM\DataLayer\Tag\Store\CurrentStore;

class UserData implements EventInterface
{
    /**
     * @param Customer $cartItems
     */
    public function __construct(private readonly PageTitle $pageTitle, private readonly PageType $pageType, private readonly CurrentStore $currentStore)
    {
    }

    /**
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function get(): array
    {
        return [
            'event' => 'trytagging_user_data',
            'page' => [
                'title' => $this->pageTitle->get(),
                'type' => $this->pageType->get()
            ],
            'store' => $this->currentStore->get(),
        ];
    }
}
