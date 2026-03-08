<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Util\GetCurrentCategory;
use Tagging\GTM\Util\GetCurrentCategoryProducts;
use Tagging\GTM\DataLayer\Mapper\ProductDataMapper;

class Products implements TagInterface
{
    /**
     * @param GetCurrentCategoryProducts $getCurrentCategoryProducts
     * @param GetCurrentCategory $getCurrentCategory
     * @param ProductDataMapper $productDataMapper
     */
    public function __construct(private readonly GetCurrentCategoryProducts $getCurrentCategoryProducts, private readonly GetCurrentCategory $getCurrentCategory, private readonly ProductDataMapper $productDataMapper)
    {
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function get(): array
    {
        $productsData = [];
        $i = 1;
        foreach ($this->getCurrentCategoryProducts->getProducts() as $product) {
            $product->setCategory($this->getCurrentCategory->get());
            $productData = $this->productDataMapper->mapByProduct($product);
            $productData['quantity'] = 1;
            $productData['index'] = $i;
            $productsData[] = $productData;
            $i++;
        }

        return $productsData;
    }
}
