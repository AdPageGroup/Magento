<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Product;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Util\CategoryProvider;

class ProductCategory implements ProductTagInterface
{
    private Product $product;

    /**
     * @param CategoryProvider $categoryProvider
     */
    public function __construct(private readonly CategoryProvider $categoryProvider)
    {
    }

    /**
     * @param Product $product
     * @return $this
     */
    #[\Override]
    public function setProduct(Product $product): ProductCategory
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function get(): string
    {
        /** @var Category|null $category */
        $category = $this->product->getCategory();
        if (is_object($category) && $category instanceof CategoryInterface) {
            return $category->getName();
        }

        try {
            return $this->categoryProvider->getFirstByProduct($this->product)->getName();
        } catch (NoSuchEntityException) {
            return '';
        }
    }
}
