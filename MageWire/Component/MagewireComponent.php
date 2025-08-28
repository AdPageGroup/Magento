<?php declare(strict_types=1);

namespace Tagging\GTM\MageWire\Component;

class MagewireComponent extends \Magewirephp\Magewire\Component
{
    public function isHyvaCheckoutEnabled(): bool
    {
        return true;
    }
} 