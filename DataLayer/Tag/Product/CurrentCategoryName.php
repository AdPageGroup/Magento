<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Product;

use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Util\GetCurrentProduct;

class CurrentCategoryName implements TagInterface
{
    /**
     * @param GetCurrentProduct $getCurrentProduct
     * @param ProductCategory $productCategory
     */
    public function __construct(private readonly GetCurrentProduct $getCurrentProduct, private readonly ProductCategory $productCategory)
    {
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function get(): string
    {
        $currentProduct = $this->getCurrentProduct->get();
        return $this->productCategory->setProduct($currentProduct)->get();
    }
}
