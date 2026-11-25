<?php

declare(strict_types=1);

namespace Tagging\GTM\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Model\AgentFilter\Loader;
use Tagging\GTM\Model\AgentFilter\SkipScript;
use Throwable;

/**
 * The agent filter list of the container: visitors whose User-Agent is on it do not load the tagging scripts
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
     * @return string The script that sets window.trytagging_skip, empty when there is no list
     */
    public function getSkipScript(): string
    {
        if ($this->skipScript === null) {
            $this->skipScript = SkipScript::render($this->getUserAgents());
        }

        return $this->skipScript;
    }

    /**
     * @return string[]
     */
    private function getUserAgents(): array
    {
        if (!$this->config->isEnabled()) {
            return [];
        }

        // A host name, optionally with a port. Anything else is not downloaded
        $domain = trim((string)$this->config->getGoogleTagmanagerUrl());
        if (!preg_match('/^[a-z0-9.-]+(:[0-9]+)?$/i', $domain)) {
            return [];
        }

        try {
            return $this->loader->getUserAgents($domain);
        } catch (Throwable $e) {
            // Fail-open: without a list everything loads as before
            return [];
        }
    }
}
