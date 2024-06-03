<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Mapper;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Util\Attribute\GetAttributeValue;

class CategoryDataMapper
{
    private Config $config;
    private GetAttributeValue $getAttributeValue;

    /**
     * @param Config $config
     * @param GetAttributeValue $getAttributeValue
     */
    public function __construct(
        Config $config,
        GetAttributeValue $getAttributeValue
    ) {
        $this->config = $config;
        $this->getAttributeValue = $getAttributeValue;
    }

    /**
     * @param CategoryInterface $category
     * @return array
     * @throws LocalizedException
     */
    public function mapByCategory(CategoryInterface $category): array
    {
        $prefix = 'category_';
        $categoryData = [];
        $categoryFields = $this->getCategoryFields();
        foreach ($categoryFields as $categoryAttributeCode) {
            $dataLayerKey = $prefix . $categoryAttributeCode;
            $attributeValue = $this->getAttributeValue->getCategoryAttributeValue($category, $categoryAttributeCode);
            if (empty($attributeValue)) {
                continue;
            }

            $categoryData[$dataLayerKey] = $attributeValue;
        }

        return $categoryData;
    }

    /**
     * @return string[]
     */
    private function getCategoryFields(): array
    {
        return array_filter(['id', 'name']);
    }
}
