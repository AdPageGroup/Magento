<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use Tagging\GTM\DataLayer\Mapper\CategoryDataMapper;
use Tagging\GTM\Api\Data\MergeTagInterface;
use Tagging\GTM\Util\GetCurrentCategory;

class CurrentCategory implements MergeTagInterface
{
    /**
     * @param GetCurrentCategory $getCurrentCategory
     * @param CategoryDataMapper $categoryDataMapper
     */
    public function __construct(private readonly GetCurrentCategory $getCurrentCategory, private readonly CategoryDataMapper $categoryDataMapper)
    {
    }

    /**
     * @return string[]
     * @throws NoSuchEntityException
     */
    #[\Override]
    public function merge(): array
    {
        $currentCategory = $this->getCurrentCategory->get();
        return $this->categoryDataMapper->mapByCategory($currentCategory);
    }
}
