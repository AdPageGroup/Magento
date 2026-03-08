<?php
declare(strict_types=1);

namespace Tagging\GTM\Util;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class GetCurrentProduct
{
    public function __construct(private readonly RequestInterface $request, private readonly ProductRepositoryInterface $productRepository, private readonly StoreManagerInterface $storeManager)
    {
    }
    
    /**
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function get(): ProductInterface
    {
        $productId = (int)$this->request->getParam('id');
        if ($this->request->getActionName() === 'configure' || empty($productId)) {
            $productId = (int)$this->request->getParam('product_id');
        }
        
        return $this->productRepository->getById($productId, false, $this->storeManager->getStore()->getId());
    }
}
