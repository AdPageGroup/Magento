<?php declare(strict_types=1);

// phpcs:ignoreFile -- Too many issues, lol

namespace Tagging\GTM\Test\Integration\Page;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Registry;
use Tagging\GTM\Test\Integration\FixtureTrait\CreateCategory;
use Tagging\GTM\Test\Integration\FixtureTrait\CreateProduct;
use Tagging\GTM\Test\Integration\PageTestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\Layout\AssertHandleInLayout;

/**
 * @magentoAppArea frontend
 */
class CategoryPageTest extends PageTestCase
{
    use CreateCategory;
    use CreateProduct;
    use AssertHandleInLayout;

    private const CATEGORY_ID = 3;
    private const NUMBER_OF_PRODUCTS = 3;

    protected function tearDown(): void
    {
        $this->removeFixtures();
        $this->reindex();
        parent::tearDown();
    }

    /**
     * The listing reads its products from the indexes, which need DDL statements to be rebuilt.
     * Those are not allowed within a transaction, so the fixtures are removed by hand instead.
     *
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     */
    public function testValidDataLayerWithOneCategory()
    {
        $this->assertEnabledFlagIsWorking();

        $this->removeFixtures();

        /** @var CategoryInterface $category */
        $category = $this->createCategory(self::CATEGORY_ID);
        $this->createProducts(self::NUMBER_OF_PRODUCTS, ['category_ids' => [$category->getId()]]);
        $this->reindex();

        $this->dispatch('catalog/category/view/id/' . $category->getId());
        $this->assertRequestActionName('view');

        $body = $this->getResponse()->getBody();
        $this->assertStringContainsString($category->getName(), $body);
        $this->assertStringContainsString('"trytagging_view_item_list"', $body);

        $block = $this->layout->getBlock('Tagging_GTM.data-layer');
        $this->assertNotEmpty($block);

        $this->assertDataLayerEquals($category->getName(), 'category_name');
        $this->assertDataLayerEquals($category->getId(), 'category_id');
        $this->assertDataLayerEquals(self::NUMBER_OF_PRODUCTS, 'category_size');
        $this->assertDataLayerEquals('category', 'page_type');

        $event = $this->getEventFromDataLayerEvents('view_item_list_event', 'trytagging_view_item_list');
        $this->assertArrayHasKey('ecommerce', $event);
        $this->assertArrayHasKey('items', $event['ecommerce']);
        $this->assertCount(self::NUMBER_OF_PRODUCTS, $event['ecommerce']['items'], var_export($event, true));

        foreach ($event['ecommerce']['items'] as $productData) {
            $this->assertNotEmpty($productData['item_id']);
            $this->assertNotEmpty($productData['item_sku']);
            $this->assertNotEmpty($productData['item_list_name']);
        }
    }

    /**
     * The storefront listing only shows what the indexers have picked up, and they do not run on
     * save while they are in "Update by Schedule" mode
     *
     * @return void
     */
    private function reindex(): void
    {
        $indexerRegistry = $this->objectManager->get(IndexerRegistry::class);
        $indexerIds = [
            'cataloginventory_stock',
            'catalog_category_product',
            'catalog_product_price',
            'catalogsearch_fulltext',
        ];

        foreach ($indexerIds as $indexerId) {
            $indexerRegistry->get($indexerId)->reindexAll();
        }
    }

    /**
     * @return void
     */
    private function removeFixtures(): void
    {
        $registry = $this->objectManager->get(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        for ($id = 1; $id <= self::NUMBER_OF_PRODUCTS; $id++) {
            try {
                $productRepository->delete($productRepository->getById($id, false, null, true));
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }

        $categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        try {
            $categoryRepository->deleteByIdentifier(self::CATEGORY_ID);
        } catch (NoSuchEntityException $e) {
            // Nothing to remove
        }

        $registry->unregister('isSecureArea');
    }
}
