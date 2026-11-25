<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\AgentFilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\AgentFilter\SkipScript;

class SkipScriptTest extends TestCase
{
    public function testNoListNoScript(): void
    {
        $this->assertSame('', SkipScript::render([]));
        $this->assertSame('', SkipScript::render(['<script>', 'abc']));
    }

    public function testCarriesTheLowercaseListAndSetsTheFlagOnce(): void
    {
        $script = SkipScript::render(['Meta-WebIndexer', 'meta-webindexer', 'HeadlessChrome']);

        $this->assertStringContainsString('})(["meta-webindexer","headlesschrome"]);', $script);
        $this->assertStringContainsString("if (typeof window.trytagging_skip !== 'undefined') {", $script);
        $this->assertStringContainsString("String(navigator.userAgent || '').toLowerCase()", $script);
        $this->assertStringContainsString('window.trytagging_skip = userAgents.some(', $script);
        $this->assertStringContainsString('userAgent.indexOf(part) !== -1', $script);
    }

    public function testCannotCloseTheScriptTag(): void
    {
        $script = SkipScript::render(['Mozilla/5.0 (X11; Linux)', '</script><script>alert(1)</script>']);

        $this->assertStringNotContainsString('</', $script);
        $this->assertStringNotContainsString('<script', $script);
        $this->assertStringContainsString('"mozilla\/5.0 (x11; linux)"', $script);
    }
}
