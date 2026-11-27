<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Cart;

use Magento\Checkout\Model\Cart as CartModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Util\PriceFormatter;
use Tagging\GTM\Util\PriceVisibility;

class CartValue implements TagInterface
{
    private CartModel $cartModel;
    private PriceFormatter $priceFormatter;
    private PriceVisibility $priceVisibility;

    /**
     * @param CartModel $cartModel
     * @param PriceFormatter $priceFormatter
     * @param PriceVisibility $priceVisibility
     */
    public function __construct(
        CartModel $cartModel,
        PriceFormatter $priceFormatter,
        PriceVisibility $priceVisibility
    ) {
        $this->cartModel = $cartModel;
        $this->priceFormatter = $priceFormatter;
        $this->priceVisibility = $priceVisibility;
    }

    /**
     * @return float|null
     */
    public function get(): ?float
    {
        return $this->priceVisibility->filter(
            $this->priceFormatter->format((float)$this->cartModel->getQuote()->getBaseGrandTotal())
        );
    }
}
