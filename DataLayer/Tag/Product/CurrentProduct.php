<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\DataLayer\Mapper\ProductDataMapper;
use Tagging\GTM\Api\Data\MergeTagInterface;
use Tagging\GTM\Util\GetCurrentProduct;

class CurrentProduct implements MergeTagInterface
{
    private ?ProductInterface $product = null;

    /**
     * @param GetCurrentProduct $getCurrentProduct
     * @param ProductDataMapper $productDataMapper
     */
    public function __construct(private readonly GetCurrentProduct $getCurrentProduct, private readonly ProductDataMapper $productDataMapper)
    {
    }

    /**
     * @return string[]
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function merge(): array
    {
        return $this->productDataMapper->mapByProduct($this->getProduct());
    }

    /**
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct(): ProductInterface
    {
        if ($this->product instanceof ProductInterface) {
            return $this->product;
        }

        return $this->getCurrentProduct->get();
    }

    /**
     * @param ProductInterface $product
     * @return void
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
    }
}
