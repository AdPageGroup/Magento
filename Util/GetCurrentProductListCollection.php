<?php declare(strict_types=1);

namespace Tagging\GTM\Util;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Collection\AbstractDb;

class GetCurrentProductListCollection
{
    private Resolver $layerResolver;

    /**
     * @param Resolver $layerResolver
     */
    public function __construct(Resolver $layerResolver)
    {
        $this->layerResolver = $layerResolver;
    }

    /**
     * @return AbstractDb|null The loaded listing collection, or null when there is none (yet)
     */
    public function get(): ?AbstractDb
    {
        try {
            $collection = $this->layerResolver->get()->getProductCollection();
        } catch (\Throwable $e) {
            return null;
        }

        if (!$collection instanceof AbstractDb || !$collection->isLoaded()) {
            return null;
        }

        return $collection;
    }
}
