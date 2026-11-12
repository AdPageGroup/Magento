<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\Util;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\DataLayer\Mapper\ProductDataMapper;
use Tagging\GTM\Test\Integration\FixtureTrait\CreateProduct;
use Tagging\GTM\Util\PriceVisibility;

/**
 * The visitor is a guest in these tests, so the customer group is NOT LOGGED IN (0).
 *
 * @magentoAppArea frontend
 */
class PriceVisibilityTest extends TestCase
{
    use CreateProduct;

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     */
    public function testPriceIsExposedWhenTheFeatureIsDisabled()
    {
        $this->assertTrue($this->getPriceVisibility()->isAllowed());

        $productData = $this->mapProduct();
        $this->assertArrayHasKey('price', $productData);
        $this->assertGreaterThan(0, $productData['price']);
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/price_visibility/enabled 1
     * @magentoConfigFixture current_store GTM/price_visibility/customer_groups 0
     * @magentoConfigFixture current_store GTM/price_visibility/mode remove
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     */
    public function testPriceIsLeftOutForAGuestInRemoveMode()
    {
        $this->assertFalse($this->getPriceVisibility()->isAllowed());
        $this->assertArrayNotHasKey('price', $this->mapProduct());
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/price_visibility/enabled 1
     * @magentoConfigFixture current_store GTM/price_visibility/customer_groups 0
     * @magentoConfigFixture current_store GTM/price_visibility/mode zero
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     */
    public function testPriceIsZeroedForAGuestInZeroMode()
    {
        $this->assertFalse($this->getPriceVisibility()->isAllowed());

        $productData = $this->mapProduct();
        $this->assertArrayHasKey('price', $productData);
        $this->assertEquals(0.0, $productData['price']);
    }

    /**
     * A guest is in group 0, so selecting only group 1 must leave the price alone.
     *
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/price_visibility/enabled 1
     * @magentoConfigFixture current_store GTM/price_visibility/customer_groups 1
     * @magentoConfigFixture current_store GTM/price_visibility/mode remove
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     */
    public function testPriceIsExposedForACustomerGroupThatIsNotSelected()
    {
        $this->assertTrue($this->getPriceVisibility()->isAllowed());

        $productData = $this->mapProduct();
        $this->assertArrayHasKey('price', $productData);
        $this->assertGreaterThan(0, $productData['price']);
    }

    /**
     * @return array
     */
    private function mapProduct(): array
    {
        /** @var ProductInterface $product */
        $product = $this->createProduct(1);
        $productDataMapper = ObjectManager::getInstance()->get(ProductDataMapper::class);

        return $productDataMapper->mapByProduct($product);
    }

    /**
     * @return PriceVisibility
     */
    private function getPriceVisibility(): PriceVisibility
    {
        return ObjectManager::getInstance()->get(PriceVisibility::class);
    }
}
