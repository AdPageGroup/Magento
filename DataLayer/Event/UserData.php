<?php

declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Tag\PageTitle;
use Tagging\GTM\DataLayer\Tag\PagePath;
use Tagging\GTM\DataLayer\Tag\Store\CurrentStore;

class UserData implements EventInterface
{
    private PageTitle $pageTitle;
    private PagePath $pagePath;
    private CurrentStore $currentStore;

    /**
     * @param Customer $cartItems
     */
    public function __construct(
        PageTitle $pageTitle,
        PagePath $pagePath,
        CurrentStore $currentStore
    ) {
        $this->pageTitle = $pageTitle;
        $this->pagePath = $pagePath;
        $this->currentStore = $currentStore;
    }

    /**
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        return [
            'event' => 'trytagging_user_data',
            'page' => [
                'title' => $this->pageTitle->get(),
                'location' => $this->pagePath->get()
            ]
        ];
    }
}
