<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Page;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Tagging\GTM\Api\Data\TagInterface;

class VirtualPage implements TagInterface
{
    public function __construct(private readonly StoreManagerInterface $storeManager)
    {
    }

    #[\Override]
    public function get(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $url = $store->getCurrentUrl();
        $urlData = parse_url($url);
        return isset($urlData['path']) ? rtrim($urlData['path'], '/') : '';
    }
}
