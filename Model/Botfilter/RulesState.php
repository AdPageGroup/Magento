<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

/**
 * What is stored for one container after a fetch, and whether the storefront changed.
 *
 * - A fetch that worked stores its rules and the time; the page changes when the rules do.
 * - A failed fetch (AdPage unreachable, key replaced) keeps the last rules, up to seven
 *   days after the last good fetch. Then they are dropped, so a forgotten store never keeps
 *   skipping visitors.
 *
 * Plain PHP, no Magento: the record is what RulesStore keeps in the flag table.
 */
class RulesState
{
    public const MAX_AGE_SECONDS = 7 * 86400;

    /**
     * @param array|null $stored {rules: array, fetched_at: int} or null
     * @return array{0: array|null, 1: bool} The record to store (null: remove it) and whether the rules in use changed
     */
    public static function next(?array $stored, ?StorefrontRules $fetched, int $now): array
    {
        $current = self::current($stored, $now);

        if ($fetched !== null) {
            return [['rules' => $fetched->toArray(), 'fetched_at' => $now], !$fetched->equals($current)];
        }

        if ($stored !== null && !self::isFresh($stored, $now)) {
            return [null, !self::rulesOf($stored)->isEmpty()];
        }

        return [$stored, false];
    }

    /** The rules the storefront uses now: the stored ones while they are fresh, otherwise none. */
    public static function current(?array $stored, int $now): StorefrontRules
    {
        if ($stored === null || !self::isFresh($stored, $now)) {
            return StorefrontRules::empty();
        }

        return self::rulesOf($stored);
    }

    private static function isFresh(array $stored, int $now): bool
    {
        $fetchedAt = (int)($stored['fetched_at'] ?? 0);

        return $fetchedAt > 0 && $now - $fetchedAt < self::MAX_AGE_SECONDS;
    }

    private static function rulesOf(array $stored): StorefrontRules
    {
        return StorefrontRules::fromArray($stored['rules'] ?? []);
    }
}
