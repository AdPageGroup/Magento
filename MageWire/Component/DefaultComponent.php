<?php declare(strict_types=1);

namespace Tagging\GTM\MageWire\Component;

class DefaultComponent extends AbstractComponent
{
    public function isHyvaCheckoutEnabled(): bool
    {
        return false;
    }
} 