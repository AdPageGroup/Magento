<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\Botfilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\Botfilter\SkipScript;
use Tagging\GTM\Model\Botfilter\StorefrontRules;

class SkipScriptTest extends TestCase
{
    public function testNoRulesNoScript(): void
    {
        $this->assertSame('', SkipScript::render(StorefrontRules::empty()));
    }

    public function testCarriesTheRulesAndSetsTheFlagOnce(): void
    {
        $script = SkipScript::render(StorefrontRules::fromArray(['userAgents' => ['meta-webindexer'], 'urls' => ['brandline[1]']]));
        $this->assertStringContainsString('["meta-webindexer"]', $script);
        $this->assertStringContainsString('["brandline[1]"]', $script);
        $this->assertStringContainsString("if (typeof window.trytagging_skip !== 'undefined') return;", $script);
        $this->assertStringContainsString('window.trytagging_skip = skip;', $script);
    }

    public function testCannotCloseTheScriptTag(): void
    {
        $script = SkipScript::render(StorefrontRules::fromArray(['userAgents' => ['Mozilla/5.0 (X11; Linux)'], 'urls' => ['/a&b=c']]));
        $this->assertStringNotContainsString('</', $script);
        $this->assertStringNotContainsString('a&b', $script);
        $this->assertStringContainsString('"\/a' . '\\' . 'u0026b=c"', $script, 'The ampersand of a rule is escaped');
    }
}
