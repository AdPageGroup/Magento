<?php declare(strict_types=1);

namespace Tagging\GTM\Util;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Collection\AbstractDb;

class GetCurrentProductListCollection
{
    private Resolver $layerResolver;

    /**
     * @var bool
     */
    private $listRendered = false;

    /**
     * @param Resolver $layerResolver
     */
    public function __construct(Resolver $layerResolver)
    {
        $this->layerResolver = $layerResolver;
    }

    /**
     * Called once a product list block has been rendered, see the MarkProductListRendered plugin
     *
     * @return void
     */
    public function markListRendered(): void
    {
        $this->listRendered = true;
    }

    /**
     * Only a rendered product list counts: on a category that shows static blocks only, navigation
     * extensions can still load the listing collection while the visitor sees no products at all
     *
     * @return AbstractDb|null The loaded listing collection, or null when there is none (yet)
     */
    public function get(): ?AbstractDb
    {
        if ($this->listRendered === false) {
            return null;
        }

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
