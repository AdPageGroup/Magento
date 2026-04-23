<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\EventInterface;
use Tagging\GTM\DataLayer\Mapper\ProductDataMapper;
use Tagging\GTM\DataLayer\Tag\CurrencyCode;
use Tagging\GTM\Util\PriceFormatter;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;

class AddToCart implements EventInterface
{
    private Product $product;
    private int $qty = 1;

    /**
     * @param ProductDataMapper $productDataMapper
     * @param CurrencyCode $currencyCode
     */
    public function __construct(private readonly ProductDataMapper $productDataMapper, private readonly CurrencyCode $currencyCode, private readonly PriceFormatter $priceFormatter, private readonly ProductRepositoryInterface $productRepository)
    {
    }

    /**
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function get(): array
    {
        $qty = ($this->qty > 0) ? $this->qty : 1;

        $product = $this->product;

        try {
            $product = $this->productRepository->get($this->product->getSku());
        } catch (Exception) {
            // Continue normal product flow since the sku is not found.
        }
        
        $itemData = $this->productDataMapper->mapByProduct($product);
        $itemData['quantity'] = $qty;
        $value = $itemData['price'] * $qty;

        return [
            'event' => 'trytagging_add_to_cart',
            'ecommerce' => [
                'currency' => $this->currencyCode->get(),
                'value' => $this->priceFormatter->format((float)$value),
                'items' => [$itemData]
            ]
        ];
    }

    /**
     * @param Product $product
     * @return AddToCart
     */
    public function setProduct(Product $product): AddToCart
    {
        $this->product = $product;
        return $this;
    }

    public function setQty(int $qty): AddToCart
    {
        $this->qty = $qty;
        return $this;
    }
}
