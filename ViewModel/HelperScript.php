<?php

declare(strict_types=1);

namespace Tagging\GTM\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Model\HelperScript\Fetcher;
use Tagging\GTM\Model\HelperScript\Loader;

/**
 * The helper script (window.taggingHelpers) that adds the marketing and device data to the dataLayer events
 */
class HelperScript implements ArgumentInterface
{
    private Config $config;
    private Loader $loader;
    private Fetcher $fetcher;

    public function __construct(
        Config $config,
        Loader $loader,
        Fetcher $fetcher
    ) {
        $this->config = $config;
        $this->loader = $loader;
        $this->fetcher = $fetcher;
    }

    public function getUrl(): string
    {
        return $this->fetcher->getUrl($this->getDomain());
    }

    /**
     * @return string|null The script to place inline, null to load it from the tagging domain instead
     */
    public function getInlineScript(): ?string
    {
        if (!$this->config->isHelperScriptInline()) {
            return null;
        }

        // A host name, optionally with a port. Anything else is not downloaded
        $domain = $this->getDomain();
        if (!preg_match('/^[a-z0-9.-]+(:[0-9]+)?$/i', $domain)) {
            return null;
        }

        return $this->loader->getScript($domain);
    }

    private function getDomain(): string
    {
        return trim($this->config->getGoogleTagmanagerUrl());
    }
}
