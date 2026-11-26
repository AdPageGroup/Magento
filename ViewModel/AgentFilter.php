<?php

declare(strict_types=1);

namespace Tagging\GTM\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Model\AgentFilter\Filter;
use Tagging\GTM\Model\AgentFilter\Loader;
use Tagging\GTM\Model\AgentFilter\SkipScript;
use Throwable;

/**
 * The agent filter of the container: visitors it matches do not load the tagging scripts
 */
class AgentFilter implements ArgumentInterface
{
    private Config $config;
    private Loader $loader;
    private ?string $skipScript = null;

    public function __construct(
        Config $config,
        Loader $loader
    ) {
        $this->config = $config;
        $this->loader = $loader;
    }

    /**
     * @return string The script that sets window.trytagging_skip, empty when nothing is filtered
     */
    public function getSkipScript(): string
    {
        if ($this->skipScript === null) {
            $this->skipScript = SkipScript::render($this->getFilter());
        }

        return $this->skipScript;
    }

    private function getFilter(): Filter
    {
        if (!$this->config->isEnabled()) {
            return Filter::none();
        }

        // A host name, optionally with a port. Anything else is not downloaded
        $domain = trim((string)$this->config->getGoogleTagmanagerUrl());
        if (!preg_match('/^[a-z0-9.-]+(:[0-9]+)?$/i', $domain)) {
            return Filter::none();
        }

        try {
            return $this->loader->getFilter($domain);
        } catch (Throwable $e) {
            // Fail-open: without a filter everything loads as before
            return Filter::none();
        }
    }
}
