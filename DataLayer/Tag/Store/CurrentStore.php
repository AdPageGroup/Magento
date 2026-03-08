<?php

declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Store;

use Tagging\GTM\Api\Data\TagInterface;
use Magento\Store\Model\StoreManagerInterface;

class CurrentStore implements TagInterface
{
    public function __construct(private readonly StoreManagerInterface $storeManager)
    {
    }

    #[\Override]
    public function get(): array
    {
        try {
            return [
                'code' => $this->storeManager->getStore()->getCode(),
                'name' => $this->storeManager->getStore()->getName(),
                'website_id' => $this->storeManager->getStore()->getWebsiteId(),
                'url' => $this->storeManager->getStore()->getCurrentUrl(),
            ];
        } catch (\Exception) {
            return [
                'code' => null,
                'name' => null,
                'website_id' => null,
                'url' => null,
            ];
        }
    }
}
