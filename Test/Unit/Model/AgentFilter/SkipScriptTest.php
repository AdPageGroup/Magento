<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\AgentFilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\AgentFilter\Filter;
use Tagging\GTM\Model\AgentFilter\SkipScript;

class SkipScriptTest extends TestCase
{
    public function testNothingFilteredNoScript(): void
    {
        $this->assertSame('', SkipScript::render(Filter::none()));
        $this->assertSame('', SkipScript::render(new Filter(['<script>', 'abc'], false)));
    }

    public function testCarriesTheLowercaseListAndSetsTheFlagOnce(): void
    {
        $script = SkipScript::render(new Filter(['Meta-WebIndexer', 'meta-webindexer', 'HeadlessChrome'], false));

        $this->assertStringContainsString('})(["meta-webindexer","headlesschrome"], false);', $script);
        $this->assertStringContainsString("if (typeof window.trytagging_skip !== 'undefined') {", $script);
        $this->assertStringContainsString("String(navigator.userAgent || '').toLowerCase()", $script);
        $this->assertStringContainsString('userAgent.indexOf(part) !== -1', $script);
    }

    public function testAutomationAloneGivesAScript(): void
    {
        $script = SkipScript::render(new Filter([], true));

        $this->assertStringContainsString('})([], true);', $script);
        $this->assertStringContainsString('window.trytagging_skip = (automation && automated) || userAgents.some(', $script);
        $this->assertStringContainsString('navigator.webdriver === true', $script);
        $this->assertStringContainsString("indexOf('headlesschrome') !== -1", $script);
        $this->assertStringContainsString("userAgent === ''", $script);
    }

    public function testCannotCloseTheScriptTag(): void
    {
        $script = SkipScript::render(new Filter(['Mozilla/5.0 (X11; Linux)', '</script><script>alert(1)</script>'], true));

        $this->assertStringNotContainsString('</', $script);
        $this->assertStringNotContainsString('<script', $script);
        $this->assertStringContainsString('"mozilla\/5.0 (x11; linux)"', $script);
    }
}
