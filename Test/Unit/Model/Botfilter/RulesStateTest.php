<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\Botfilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\Botfilter\RulesState;
use Tagging\GTM\Model\Botfilter\StorefrontRules;

class RulesStateTest extends TestCase
{
    private const NOW = 1795000000;

    private function rules(array $userAgents): StorefrontRules
    {
        return StorefrontRules::fromArray(['userAgents' => $userAgents]);
    }

    private function stored(array $userAgents, int $ageSeconds): array
    {
        return ['rules' => $this->rules($userAgents)->toArray(), 'fetched_at' => self::NOW - $ageSeconds];
    }

    public function testAFirstFetchWithRulesChangesThePage(): void
    {
        [$next, $changed] = RulesState::next(null, $this->rules(['meta-webindexer']), self::NOW);
        $this->assertSame(['rules' => ['userAgents' => ['meta-webindexer'], 'urls' => []], 'fetched_at' => self::NOW], $next);
        $this->assertTrue($changed);
    }

    public function testTheSameRulesOnlyRefreshTheTime(): void
    {
        [$next, $changed] = RulesState::next($this->stored(['meta-webindexer'], 300), $this->rules(['meta-webindexer']), self::NOW);
        $this->assertSame(self::NOW, $next['fetched_at']);
        $this->assertFalse($changed);
    }

    public function testEmptyRulesFromAdPageClearTheStore(): void
    {
        [$next, $changed] = RulesState::next($this->stored(['meta-webindexer'], 300), StorefrontRules::empty(), self::NOW);
        $this->assertSame([], $next['rules']['userAgents']);
        $this->assertTrue($changed);
    }

    public function testAFailedFetchKeepsTheLastRulesForSevenDays(): void
    {
        $stored = $this->stored(['meta-webindexer'], RulesState::MAX_AGE_SECONDS - 60);
        [$next, $changed] = RulesState::next($stored, null, self::NOW);
        $this->assertSame($stored, $next);
        $this->assertFalse($changed);
        $this->assertSame(['meta-webindexer'], RulesState::current($stored, self::NOW)->getUserAgents());
    }

    public function testAfterSevenDaysWithoutAGoodFetchTheRulesAreDropped(): void
    {
        $stored = $this->stored(['meta-webindexer'], RulesState::MAX_AGE_SECONDS + 60);
        [$next, $changed] = RulesState::next($stored, null, self::NOW);
        $this->assertNull($next);
        $this->assertTrue($changed);
        $this->assertTrue(RulesState::current($stored, self::NOW)->isEmpty());
    }

    public function testDroppingStaleEmptyRulesDoesNotChangeThePage(): void
    {
        [$next, $changed] = RulesState::next($this->stored([], RulesState::MAX_AGE_SECONDS + 60), null, self::NOW);
        $this->assertNull($next);
        $this->assertFalse($changed);
    }

    public function testAFailedFirstFetchStoresNothing(): void
    {
        $this->assertSame([null, false], RulesState::next(null, null, self::NOW));
    }

    public function testAGoodFetchAfterStaleRulesIsAChangeOnlyIfTheRulesDiffer(): void
    {
        $stale = $this->stored(['meta-webindexer'], RulesState::MAX_AGE_SECONDS + 60);
        [, $changed] = RulesState::next($stale, $this->rules(['meta-webindexer']), self::NOW);
        $this->assertTrue($changed, 'The stale rules were not in use any more, so using them again is a change');
    }
}
