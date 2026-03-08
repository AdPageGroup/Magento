<?php declare(strict_types=1);

namespace Tagging\GTM\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Tagging\GTM\Exception\BlockNotFound;

class AddProductDetails
{
    public function __construct(private readonly LayoutInterface $layout)
    {
    }

    /**
     * @param AbstractProduct $abstractProduct
     * @param ProductInterface $product
     * @return string
     */
    public function afterGetProductDetailsHtml(AbstractProduct $abstractProduct, mixed $html, ProductInterface $product)
    {
        try {
            $block = $this->getProductDetailsBlock();
        } catch (BlockNotFound) {
            return $html;
        }

        $html .= $block->setProduct($product)->toHtml(); // @phpstan-ignore-line
        return $html;
    }

    /**
     * @return BlockInterface
     * @throws BlockNotFound
     */
    private function getProductDetailsBlock(): BlockInterface
    {
        $block = $this->layout->getBlock('Tagging_GTM.product-details');
        if ($block instanceof BlockInterface) {
            return $block;
        }

        throw new BlockNotFound('Block "Tagging_GTM.product-details" not found');
    }
}
