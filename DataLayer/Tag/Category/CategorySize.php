<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag\Category;

use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Util\GetCurrentProductListCollection;

class CategorySize implements TagInterface
{
    private GetCurrentProductListCollection $getCurrentProductListCollection;

    /**
     * @var int|null
     */
    private $size = null;

    /**
     * @param GetCurrentProductListCollection $getCurrentProductListCollection
     */
    public function __construct(GetCurrentProductListCollection $getCurrentProductListCollection)
    {
        $this->getCurrentProductListCollection = $getCurrentProductListCollection;
    }

    /**
     * @param int $size
     * @return void
     */
    public function setSize(int $size = 0)
    {
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function get(): int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        $collection = $this->getCurrentProductListCollection->get();

        return $collection === null ? 0 : count($collection->getItems());
    }
}
