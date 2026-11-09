<?php declare(strict_types=1);

namespace Tagging\GTM\Util;

use Magento\Catalog\Api\Data\ProductInterface;

class GetCurrentCategoryProducts
{
    private const MAXIMUM_PRODUCTS = 50;

    private GetCurrentProductListCollection $getCurrentProductListCollection;

    /**
     * @var ProductInterface[]
     */
    private $products = [];

    /**
     * @var bool
     */
    private $collected = false;

    /**
     * @param GetCurrentProductListCollection $getCurrentProductListCollection
     */
    public function __construct(GetCurrentProductListCollection $getCurrentProductListCollection)
    {
        $this->getCurrentProductListCollection = $getCurrentProductListCollection;
    }

    /**
     * @param ProductInterface $product
     * @return void
     */
    public function addProduct(ProductInterface $product)
    {
        $this->products[$product->getId()] = $product;
        $this->collected = true;
    }

    /**
     * @return ProductInterface[]
     */
    public function getProducts(): array
    {
        if ($this->collected === false) {
            $this->collected = true;
            $this->collectFromCurrentListing();
        }

        return $this->products;
    }

    /**
     * Read the products of the current listing, capped so a large page size cannot blow up the
     * dataLayer. Does nothing while the listing collection is still unloaded, so the toolbar keeps
     * control over the page size - see GetCurrentProductListCollection.
     *
     * @return void
     */
    private function collectFromCurrentListing(): void
    {
        $collection = $this->getCurrentProductListCollection->get();
        if ($collection === null) {
            return;
        }

        foreach ($collection->getItems() as $product) {
            if (count($this->products) >= self::MAXIMUM_PRODUCTS) {
                break;
            }

            if (!$product instanceof ProductInterface) {
                continue;
            }

            $this->products[$product->getId()] = $product;
        }
    }
}
