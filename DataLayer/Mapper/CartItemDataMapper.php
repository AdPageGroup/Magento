<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Mapper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Config;
use Tagging\GTM\Util\PriceFormatter;
use Tagging\GTM\Util\ProductProvider;

class CartItemDataMapper
{
    /**
     * @param ProductDataMapper $productDataMapper
     * @param ProductProvider $productProvider
     * @param PriceFormatter $priceFormatter
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ProductDataMapper $productDataMapper, private readonly ProductProvider $productProvider, private readonly PriceFormatter $priceFormatter, private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * @param CartItemInterface $cartItem
     * @return array
     * @throws LocalizedException
     */
    public function mapByCartItem(CartItemInterface $cartItem): array
    {
        try {
            $product = $this->productProvider->getBySku($cartItem->getSku());
            $cartItemData = $this->productDataMapper->mapByProduct($product);
        } catch (NoSuchEntityException) {
            $cartItemData = [];
        }

        return array_merge($cartItemData, [
            'item_sku' => $cartItem->getSku(),
            'item_name' => $cartItem->getName(),
            'order_item_id' => $cartItem->getItemId(),
            'quantity' => (float) $cartItem->getQty(),
            'price' => $this->getPrice($cartItem)
        ]);
    }

    /**
     * @param CartItemInterface $cartItem
     * @return float
     */
    private function getPrice(CartItemInterface $cartItem): float
    {
        $displayType = (int)$this->scopeConfig->getValue(
            Config::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE,
            ScopeInterface::SCOPE_STORE,
            $cartItem->getStoreId() // @phpstan-ignore-line
        );

        $price = match ($displayType) {
            Config::DISPLAY_TYPE_EXCLUDING_TAX, Config::DISPLAY_TYPE_BOTH => $cartItem->getConvertedPrice(),
            default => $cartItem->getPriceInclTax(),
        };

        return $this->priceFormatter->format((float)$price);
    }
}
