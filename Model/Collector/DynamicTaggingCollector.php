<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\Collector;

use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Model\Collector\CollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Tagging\GTM\Config\Config;
use Magento\Store\Model\StoreManagerInterface;

class DynamicTaggingCollector implements CollectorInterface
{
    private Config $config;
    private StoreManagerInterface $storeManager;

    public function __construct(Config $config, StoreManagerInterface $storeManager)
    {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    public function collect(array $defaultPolicies = []): array
    {
        try {
            // Only add policies if the module is enabled and placed by plugin
            if (!$this->config->isEnabled() || !$this->config->isPlacedByPlugin()) {
                return [];
            }

            $allowedDomains = [];
            $allowedProtocols = ['https'];

            // 1. Add the store's own domain
            try {
                $storeBaseUrl = $this->storeManager->getStore()->getBaseUrl();
                $storeDomain = parse_url($storeBaseUrl, PHP_URL_HOST);
                $storeProtocol = parse_url($storeBaseUrl, PHP_URL_SCHEME);
                
                if ($storeDomain) {
                    $storeUrl = $storeProtocol . '://' . $storeDomain;
                    $allowedDomains[] = $storeUrl;
                    
                    // Also add the protocol if it's not already included
                    if ($storeProtocol && !in_array($storeProtocol, $allowedProtocols)) {
                        $allowedProtocols[] = $storeProtocol;
                    }
                }
            } catch (\Exception $e) {
                // Continue even if store domain detection fails
            }

            // 2. Add the configured GTM URL if it exists
            $gtmUrl = $this->config->getGoogleTagmanagerUrl();
            if (!empty($gtmUrl)) {
                // Ensure the URL has proper protocol
                if (!preg_match('/^https?:\/\//', $gtmUrl)) {
                    $gtmUrl = 'https://' . $gtmUrl;
                }

                // Parse the URL to get the domain
                $parsedUrl = parse_url($gtmUrl);
                if ($parsedUrl && isset($parsedUrl['host'])) {
                    $domain = $parsedUrl['host'];
                    $protocol = $parsedUrl['scheme'] ?? 'https';
                    $taggingUrl = $protocol . '://' . $domain;
                    
                    // Only add if it's different from the store domain
                    if (!in_array($taggingUrl, $allowedDomains)) {
                        $allowedDomains[] = $taggingUrl;
                    }
                    
                    // Add protocol if not already included
                    if (!in_array($protocol, $allowedProtocols)) {
                        $allowedProtocols[] = $protocol;
                    }
                }
            }

            // If no domains to allow, return empty policies
            if (empty($allowedDomains)) {
                return [];
            }

            $policies = [
                new FetchPolicy(
                    'script-src',
                    false,
                    $allowedDomains,
                    $allowedProtocols
                ),
                new FetchPolicy(
                    'connect-src',
                    false,
                    $allowedDomains,
                    $allowedProtocols
                )
            ];

            return $policies;
            
        } catch (\Exception $e) {
            // Return empty policies to avoid breaking the site
            return [];
        }
    }
}
