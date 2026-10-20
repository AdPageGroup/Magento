<?php declare(strict_types=1);

// phpcs:ignoreFile -- Too many issues, lol

namespace Tagging\GTM\Test\Integration\Page;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
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

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoCache all disabled
     */
    public function testValidDataLayerWithOneCategory()
    {
        $this->assertEnabledFlagIsWorking();


        /** @var CategoryInterface $category */
        $category = $this->createCategory(3);
        $this->createProducts(3, ['category_ids' => [$category->getId()]]);
        $products = $category->getProductCollection();
        $this->assertTrue($products->count() >= 3, 'Product count is '.$products->count());

        $this->dispatch('catalog/category/view/id/' . $category->getId());
        $this->assertRequestActionName('view');

        $body = $this->getResponse()->getBody();
        $this->assertStringContainsString($category->getName(), $body);
        $this->assertStringContainsString('"trytagging_view_item_list"', $body);

        $block = $this->layout->getBlock('Tagging_GTM.data-layer');
        $this->assertNotEmpty($block);

        // The category products are read from the listing while the page renders, so assert
        // against the dataLayer as it was rendered instead of rebuilding it after the request
        $pushes = $this->getDataLayerPushesFromBody($body);

        $pageData = $this->findDataLayerPush($pushes, function (array $push) {
            return ($push['page_type'] ?? null) === 'category';
        });
        $this->assertEquals($category->getName(), $pageData['category_name']);
        $this->assertEquals($category->getId(), $pageData['category_id']);

        $event = $this->findDataLayerPush($pushes, function (array $push) {
            return ($push['event'] ?? null) === 'trytagging_view_item_list';
        });
        $this->assertArrayHasKey('ecommerce', $event);
        $this->assertArrayHasKey('items', $event['ecommerce']);
        $this->assertCount(3, $event['ecommerce']['items'], var_export($event, true));
        $this->assertEquals(count($event['ecommerce']['items']), $pageData['category_size']);

        foreach ($event['ecommerce']['items'] as $productData) {
            $this->assertNotEmpty($productData['item_id']);
            $this->assertNotEmpty($productData['item_sku']);
            $this->assertNotEmpty($productData['item_list_name']);
        }
    }

    /**
     * Every dataLayer push rendered in the page, as written by the Luma (`pusher({...}, 'push (...')`)
     * and Hyvä (`googleTagManager2Pusher({...}, 'push (...')`) data-layer templates
     *
     * @param string $body
     * @return array[]
     */
    private function getDataLayerPushesFromBody(string $body): array
    {
        preg_match_all("/[pP]usher\((\{.*?\}), 'push \(/s", $body, $matches);

        $pushes = [];
        foreach ($matches[1] as $json) {
            $push = json_decode($json, true);
            if (is_array($push)) {
                $pushes[] = $push;
            }
        }

        $this->assertNotEmpty($pushes, 'No dataLayer pushes found in the page');
        return $pushes;
    }

    /**
     * @param array[] $pushes
     * @param callable $matches
     * @return array
     */
    private function findDataLayerPush(array $pushes, callable $matches): array
    {
        foreach ($pushes as $push) {
            if ($matches($push)) {
                return $push;
            }
        }

        $this->fail('No matching dataLayer push in the page: ' . var_export($pushes, true));
    }
}
