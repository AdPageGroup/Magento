<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Product;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Util\GetCurrentProduct;
use Tagging\GTM\Util\PriceFormatter;
use Tagging\GTM\Util\PriceVisibility;

class CurrentPrice implements TagInterface
{
    private GetCurrentProduct $getCurrentProduct;
    private PriceFormatter $priceFormatter;
    private PriceVisibility $priceVisibility;

    /**
     * @param GetCurrentProduct $getCurrentProduct
     * @param PriceFormatter $priceFormatter
     * @param PriceVisibility $priceVisibility
     */
    public function __construct(
        GetCurrentProduct $getCurrentProduct,
        PriceFormatter $priceFormatter,
        PriceVisibility $priceVisibility
    ) {
        $this->getCurrentProduct = $getCurrentProduct;
        $this->priceFormatter = $priceFormatter;
        $this->priceVisibility = $priceVisibility;
    }

    /**
     * @return float|null
     * @throws NoSuchEntityException
     */
    public function get(): ?float
    {
        $product = $this->getCurrentProduct->get();
        return $this->priceVisibility->filter(
            $this->priceFormatter->format(
                // @phpstan-ignore-next-line
                (float) $product->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getValue() // @phpstan-ignore-line
            )
        );
    }
}
