<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Tagging\GTM\DataLayer\Tag\Category\CategorySize;
use Tagging\GTM\Util\GetCurrentCategoryProducts;

/**
 * Collect the products of a product listing so the dataLayer can report them.
 *
 * This listens to catalog_block_product_list_collection, which Magento dispatches from
 * ListProduct::_beforeToHtml() once the toolbar has applied the page size, the current page
 * and the sort order, and the collection has been loaded.
 *
 * Reading the collection any earlier - for example from an after-plugin on
 * getLoadedProductCollection() - loads it before the toolbar runs. A loaded Magento collection
 * silently ignores setPageSize() and setCurPage(), so the query loses its LIMIT and the listing
 * renders every product in the category instead of one page.
 */
class CollectProductListProducts implements ObserverInterface
{
    private const MAXIMUM_PRODUCTS = 50;

    private GetCurrentCategoryProducts $getCurrentCategoryProducts;
    private CategorySize $categorySize;

    /**
     * @param GetCurrentCategoryProducts $getCurrentCategoryProducts
     * @param CategorySize $categorySize
     */
    public function __construct(
        GetCurrentCategoryProducts $getCurrentCategoryProducts,
        CategorySize $categorySize
    ) {
        $this->getCurrentCategoryProducts = $getCurrentCategoryProducts;
        $this->categorySize = $categorySize;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $collection = $observer->getData('collection');
        if (!$collection instanceof Collection) {
            return;
        }

        $counter = 0;
        foreach ($collection->getItems() as $product) {
            if ($counter >= self::MAXIMUM_PRODUCTS) {
                break;
            }

            if (!$product instanceof ProductInterface) {
                continue;
            }

            $this->getCurrentCategoryProducts->addProduct($product);
            $counter++;
        }

        $this->categorySize->setSize($collection->count());
    }
}
