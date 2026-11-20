<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

/**
 * The rules of the AdPage Botfilter for the storefront: pieces of a User-Agent and pieces
 * of a page address. A browser or page that matches one does not load the tagging scripts,
 * so it never connects to the tagging domain. Empty lists mean: load everything.
 *
 * What comes from the API is checked again here, with the same limits as AdPage uses,
 * because the lists end up in a script on every page.
 */
class StorefrontRules
{
    public const MAX_ENTRIES = 50;
    private const USER_AGENT = '/^(?=.*[A-Za-z])[\w .\-\/();:,+]{4,200}$/';
    private const URL_PART = '/^[^\s<>"\'`\\\\]{4,200}$/';

    /** @var string[] */
    private array $userAgents;
    /** @var string[] */
    private array $urls;

    /**
     * @param string[] $userAgents
     * @param string[] $urls
     */
    private function __construct(array $userAgents, array $urls)
    {
        $this->userAgents = $userAgents;
        $this->urls = $urls;
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * @param mixed $data The decoded answer of the API, or a stored copy of it
     */
    public static function fromArray($data): self
    {
        $data = is_array($data) ? $data : [];

        return new self(
            self::clean($data['userAgents'] ?? [], self::USER_AGENT),
            self::clean($data['urls'] ?? [], self::URL_PART)
        );
    }

    /** @return string[] */
    public function getUserAgents(): array
    {
        return $this->userAgents;
    }

    /** @return string[] */
    public function getUrls(): array
    {
        return $this->urls;
    }

    public function isEmpty(): bool
    {
        return $this->userAgents === [] && $this->urls === [];
    }

    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /** @return array{userAgents: string[], urls: string[]} */
    public function toArray(): array
    {
        return ['userAgents' => $this->userAgents, 'urls' => $this->urls];
    }

    /**
     * @param mixed $list
     * @return string[]
     */
    private static function clean($list, string $pattern): array
    {
        if (!is_array($list)) {
            return [];
        }

        $entries = [];
        foreach ($list as $item) {
            if (!is_string($item)) {
                continue;
            }
            $item = trim($item);
            if (preg_match($pattern, $item) && !in_array($item, $entries, true)) {
                $entries[] = $item;
            }
        }

        return array_slice($entries, 0, self::MAX_ENTRIES);
    }
}
