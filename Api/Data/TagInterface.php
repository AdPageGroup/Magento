<?php declare(strict_types=1);

namespace Tagging\GTM\Api\Data;

use Magento\Framework\View\Element\Block\ArgumentInterface;

interface TagInterface extends ArgumentInterface
{
    /**
     * @return mixed
     */
    public function get();
}
