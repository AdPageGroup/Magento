<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag;

use Magento\Framework\UrlInterface;
use Tagging\GTM\Api\Data\TagInterface;

class PagePath implements TagInterface
{
    public function __construct(private readonly UrlInterface $url)
    {
    }

    #[\Override]
    public function get(): string
    {
        return $this->url->getCurrentUrl();
    }
}
