<?php declare(strict_types=1);

namespace Tagging\GTM\MageWire\Component;

class ComponentFactory
{
    public function create(): AbstractComponent
    {
        if (class_exists('\Magewirephp\Magewire\Component')) {
            return new MagewireComponent();
        }
        
        return new DefaultComponent();
    }
} 