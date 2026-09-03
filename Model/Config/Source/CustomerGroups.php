<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Config\Source;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * All customer groups, including the "NOT LOGGED IN" group that Magento assigns to guests.
 *
 * Magento\Customer\Model\Config\Source\Group\Multiselect is not used here on purpose: the guest
 * group is the most important option for this setting and that source model filters it out.
 */
class CustomerGroups implements OptionSourceInterface
{
    private GroupRepositoryInterface $groupRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private SortOrderFactory $sortOrderFactory;

    /**
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderFactory $sortOrderFactory
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderFactory $sortOrderFactory
    ) {
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderFactory = $sortOrderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $sortOrder = $this->sortOrderFactory->create(['field' => 'customer_group_id', 'direction' => 'asc']);
        $this->searchCriteriaBuilder->addSortOrder($sortOrder);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        try {
            $customerGroups = $this->groupRepository->getList($searchCriteria)->getItems();
        } catch (LocalizedException $localizedException) {
            return [];
        }

        $options = [];
        foreach ($customerGroups as $customerGroup) {
            $options[] = [
                'value' => (string)$customerGroup->getId(),
                'label' => $customerGroup->getCode()
            ];
        }

        return $options;
    }
}
