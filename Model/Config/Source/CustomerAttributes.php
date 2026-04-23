<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Config\Source;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerAttributes implements OptionSourceInterface
{
    private readonly SortOrderFactory $sortOrderFactory;

    public function __construct(
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderFactory $sortOrderFactory
    ) {
        $this->sortOrderFactory = $sortOrderFactory;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => '']];

        $sortOrder = $this->sortOrderFactory->create(['field' => 'attribute_code', 'direction' => 'asc']);
        $this->searchCriteriaBuilder->addSortOrder($sortOrder);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->attributeRepository->getList('customer', $searchCriteria);
        foreach ($searchResult->getItems() as $customerAttribute) {
            if (false === $this->isAttributeDisplayedInFrontend($customerAttribute)) {
                continue;
            }


            $options[] = [
                'value' => $customerAttribute->getAttributeCode(),
                'label' => $customerAttribute->getAttributeCode() . ': ' . $customerAttribute->getDefaultFrontendLabel()
            ];
        }

        return $options;
    }

    /**
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function isAttributeDisplayedInFrontend(AttributeInterface $attribute): bool
    {
        $forms = $attribute->getUsedInForms(); // @phpstan-ignore-line
        foreach ($forms as $form) {
            if (preg_match('/^customer_/', $form)) {
                return true;
            }
        }

        return false;
    }
}
