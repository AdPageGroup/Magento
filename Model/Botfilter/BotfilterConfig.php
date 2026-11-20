<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Stores › Configuration › AdPage › AdPage GTM › Botfilter: the key per website or store
 * view (saved encrypted) and the AdPage address the rules come from.
 */
class BotfilterConfig
{
    public const XML_PATH_KEY = 'GTM/botfilter/key';
    public const XML_PATH_API_URL = 'GTM/botfilter/api_url';
    public const DEFAULT_API_URL = 'https://data.adpage.io';

    private ScopeConfigInterface $scopeConfig;
    private EncryptorInterface $encryptor;

    public function __construct(ScopeConfigInterface $scopeConfig, EncryptorInterface $encryptor)
    {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * The key for a store view; the current one without an id. A value that was set
     * without encryption (for example with config:set --lock-env) works as well.
     */
    public function getKey(?int $storeId = null): string
    {
        $raw = trim((string)$this->scopeConfig->getValue(self::XML_PATH_KEY, ScopeInterface::SCOPE_STORE, $storeId));
        if ($raw === '') {
            return '';
        }

        if (BotfilterKey::parse($raw) !== null) {
            return $raw;
        }

        try {
            return trim((string)$this->encryptor->decrypt($raw));
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getApiUrl(): string
    {
        $url = trim((string)$this->scopeConfig->getValue(self::XML_PATH_API_URL));

        return rtrim(preg_match('#^https://#', $url) ? $url : self::DEFAULT_API_URL, '/');
    }
}
