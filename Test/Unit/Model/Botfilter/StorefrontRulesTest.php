<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\Botfilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\Botfilter\StorefrontRules;

class StorefrontRulesTest extends TestCase
{
    public function testKeepsValidEntriesOnceAndDropsTheRest(): void
    {
        $rules = StorefrontRules::fromArray([
            'userAgents' => ['meta-webindexer', 'meta-webindexer', 'abc', '<script>', 42, 'Chrome/145.0 (compatible)'],
            'urls' => ['brandline[1]', '/', 'with space', 'quote"d', '/sale?x=1'],
        ]);
        $this->assertSame(['meta-webindexer', 'Chrome/145.0 (compatible)'], $rules->getUserAgents());
        $this->assertSame(['brandline[1]', '/sale?x=1'], $rules->getUrls());
    }

    public function testTakesAtMostFiftyPerList(): void
    {
        $many = array_map(static function (int $i): string {
            return '/page-' . $i;
        }, range(1, 60));
        $this->assertCount(StorefrontRules::MAX_ENTRIES, StorefrontRules::fromArray(['urls' => $many])->getUrls());
    }

    public function testAnythingElseIsEmpty(): void
    {
        $this->assertTrue(StorefrontRules::fromArray(null)->isEmpty());
        $this->assertTrue(StorefrontRules::fromArray(['userAgents' => 'meta-webindexer'])->isEmpty());
        $this->assertTrue(StorefrontRules::empty()->isEmpty());
    }

    public function testComparesByContent(): void
    {
        $a = StorefrontRules::fromArray(['userAgents' => ['meta-webindexer']]);
        $b = StorefrontRules::fromArray($a->toArray());
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals(StorefrontRules::empty()));
    }
}
