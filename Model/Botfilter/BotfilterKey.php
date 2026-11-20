<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

/**
 * The key from the AdPage Botfilter page (Databoosts › Botfilter › In your Magento store):
 * the AdPage container ID and the storefront token in one value, "<container-id>:<token>".
 * The module does not know the container ID otherwise.
 */
class BotfilterKey
{
    private const PATTERN = '/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}):(bfs_[A-Za-z0-9_-]{16,128})$/i';

    private string $containerId;
    private string $token;

    private function __construct(string $containerId, string $token)
    {
        $this->containerId = $containerId;
        $this->token = $token;
    }

    public static function parse(?string $value): ?self
    {
        if (!preg_match(self::PATTERN, trim((string)$value), $match)) {
            return null;
        }

        return new self(strtolower($match[1]), $match[2]);
    }

    public function getContainerId(): string
    {
        return $this->containerId;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
