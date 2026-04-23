<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag;

use Magento\Framework\View\Page\Title;
use Tagging\GTM\Api\Data\TagInterface;

class PageTitle implements TagInterface
{
    public function __construct(private readonly Title $pageTitle)
    {
    }

    #[\Override]
    public function get(): string
    {
        return $this->pageTitle->get();
    }
}
