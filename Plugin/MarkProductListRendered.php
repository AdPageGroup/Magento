<?php declare(strict_types=1);

namespace Tagging\GTM\Plugin;

use Magento\Catalog\Block\Product\ListProduct;
use Tagging\GTM\Util\GetCurrentProductListCollection;

class MarkProductListRendered
{
    private GetCurrentProductListCollection $getCurrentProductListCollection;

    /**
     * @param GetCurrentProductListCollection $getCurrentProductListCollection
     */
    public function __construct(GetCurrentProductListCollection $getCurrentProductListCollection)
    {
        $this->getCurrentProductListCollection = $getCurrentProductListCollection;
    }

    /**
     * Only flags the list as rendered, the collection itself is left alone so the toolbar keeps
     * control over the page size
     *
     * @param ListProduct $listProductBlock
     * @param mixed $html
     * @return mixed
     */
    public function afterToHtml(ListProduct $listProductBlock, $html)
    {
        $this->getCurrentProductListCollection->markListRendered();
        return $html;
    }
}
