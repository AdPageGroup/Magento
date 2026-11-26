<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\AgentFilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\AgentFilter\Filter;

class FilterTest extends TestCase
{
    public function testKeepsOnlyValidUserAgents(): void
    {
        $filter = new Filter([' meta-webindexer ', '<script>', 'abc', 42, 'meta-webindexer'], false);

        $this->assertSame(['meta-webindexer'], $filter->getUserAgents());
    }

    public function testIsEmptyOnlyWithoutUserAgentsAndWithoutAutomation(): void
    {
        $this->assertTrue(Filter::none()->isEmpty());
        $this->assertFalse((new Filter([], true))->isEmpty());
        $this->assertFalse((new Filter(['meta-webindexer'], false))->isEmpty());
    }

    public function testFromArrayTreatsAnythingUnexpectedAsNotSet(): void
    {
        $this->assertTrue(Filter::fromArray('nonsense')->isEmpty());
        $this->assertTrue(Filter::fromArray(['userAgents' => 'meta-webindexer', 'automation' => 1])->isEmpty());
        $this->assertSame(
            ['userAgents' => ['meta-webindexer'], 'automation' => true],
            Filter::fromArray(['userAgents' => ['meta-webindexer'], 'automation' => true])->toArray()
        );
    }
}
