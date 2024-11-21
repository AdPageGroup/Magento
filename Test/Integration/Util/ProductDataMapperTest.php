<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\Util;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\DataLayer\Mapper\ProductDataMapper;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertNonEmptyValueInArray;

class ProductDataMapperTest extends TestCase
{
    use AssertNonEmptyValueInArray;

    private ProductInterfaceFactory $productFactory;
    private ProductDataMapper $productDataMapper;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = ObjectManager::getInstance();
        $this->productFactory = $objectManager->get(ProductInterfaceFactory::class);
        $this->productDataMapper = $objectManager->get(ProductDataMapper::class);
    }

    public function testMapByProduct()
    {
        $product = $this->createProduct(
            1,
            'Product 1',
            'product1',
            1.42,
        );

        $productData = $this->productDataMapper->mapByProduct($product);

        $this->assertNonEmptyValueInArray('item_id', $productData);
        $this->assertSame(1, $productData['magento_id']);
        $this->assertSame('product1', $productData['magento_sku']);
        $this->assertSame('product1', $productData['item_id']);
        $this->assertSame('product1', $productData['item_sku']);
        $this->assertSame('Product 1', $productData['item_name']);
        $this->assertSame(1.42, $productData['price']);
    }

    private function createProduct(int $id, string $name, string $sku, float $price): ProductInterface
    {
        $product = $this->productFactory->create();
        $product->setId($id);
        $product->setName($name);
        $product->setSku($sku);
        $product->setPrice($price);
        return $product;
    }
}
