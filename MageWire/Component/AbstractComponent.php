<?php declare(strict_types=1);

namespace Tagging\GTM\MageWire\Component;

use Magento\Framework\View\Element\Block\ArgumentInterface;

abstract class AbstractComponent implements ArgumentInterface
{
    abstract public function isHyvaCheckoutEnabled(): bool;
} 