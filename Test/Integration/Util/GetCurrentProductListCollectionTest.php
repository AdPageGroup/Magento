<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\Util;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Test\Integration\FixtureTrait\CreateCategory;
use Tagging\GTM\Test\Integration\FixtureTrait\CreateProduct;
use Tagging\GTM\Util\GetCurrentProductListCollection;

/**
 * @magentoAppArea frontend
 */
class GetCurrentProductListCollectionTest extends TestCase
{
    use CreateCategory;
    use CreateProduct;

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testReturnsCollectionOnProductCategory()
    {
        $getCurrentProductListCollection = $this->renderCategory(['display_mode' => Category::DM_PRODUCT]);
        $this->assertNotNull($getCurrentProductListCollection->get());
    }

    /**
     * Mirrors a static block only category on which another ListProduct block (like the Tweakwise
     * featured products block) renders and the listing collection still gets loaded
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testReturnsNothingOnStaticBlockCategory()
    {
        $getCurrentProductListCollection = $this->renderCategory([
            'display_mode' => Category::DM_PAGE,
            'is_anchor' => true,
        ]);
        $this->assertNull($getCurrentProductListCollection->get());
    }

    /**
     * Magento shows the products of a static block only anchor category once a filter is applied
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testReturnsCollectionOnFilteredStaticBlockCategory()
    {
        $getCurrentProductListCollection = $this->renderCategory([
            'display_mode' => Category::DM_PAGE,
            'is_anchor' => true,
        ]);

        $layer = ObjectManager::getInstance()->get(Resolver::class)->get();
        $layer->getState()->addFilter(ObjectManager::getInstance()->create(Item::class));

        $this->assertNotNull($getCurrentProductListCollection->get());
    }

    /**
     * @param array $categoryData
     * @return GetCurrentProductListCollection
     */
    private function renderCategory(array $categoryData): GetCurrentProductListCollection
    {
        $category = $this->createCategory(3, 2, $categoryData);
        $this->createProducts(2, ['category_ids' => [$category->getId()]]);

        $layerResolver = ObjectManager::getInstance()->get(Resolver::class);
        $layerResolver->create(Resolver::CATALOG_LAYER_CATEGORY);
        $layer = $layerResolver->get();
        $layer->setCurrentCategory((int)$category->getId());
        $layer->getProductCollection()->load();

        $getCurrentProductListCollection = ObjectManager::getInstance()->get(GetCurrentProductListCollection::class);
        $getCurrentProductListCollection->markListRendered();

        return $getCurrentProductListCollection;
    }
}
