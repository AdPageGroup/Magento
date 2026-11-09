<?php declare(strict_types=1);

namespace Tagging\GTM\Util;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer;
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
     * extensions can still load the listing collection while the visitor sees no products at all.
     * The display mode is checked as well, because other ListProduct blocks (like the Tweakwise
     * featured products block) also render on such a category and mark the list as rendered.
     *
     * @return AbstractDb|null The loaded listing collection, or null when there is none (yet)
     */
    public function get(): ?AbstractDb
    {
        if ($this->listRendered === false) {
            return null;
        }

        try {
            $layer = $this->layerResolver->get();
            if ($this->showsStaticBlocksOnly($layer)) {
                return null;
            }

            $collection = $layer->getProductCollection();
        } catch (\Throwable $e) {
            return null;
        }

        if (!$collection instanceof AbstractDb || !$collection->isLoaded()) {
            return null;
        }

        return $collection;
    }

    /**
     * Same rule as Magento\Catalog\Block\Category\View::isContentMode(): a static block only category
     * still shows its products once a layered navigation filter is applied to it
     *
     * @param Layer $layer
     * @return bool
     */
    private function showsStaticBlocksOnly(Layer $layer): bool
    {
        $category = $layer->getCurrentCategory();
        if ($category->getDisplayMode() !== Category::DM_PAGE) {
            return false;
        }

        if (!$category->getIsAnchor()) {
            return true;
        }

        $state = $layer->getState();
        return !$state || empty($state->getFilters());
    }
}
