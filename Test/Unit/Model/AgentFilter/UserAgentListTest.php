<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\AgentFilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\AgentFilter\UserAgentList;

class UserAgentListTest extends TestCase
{
    public function testKeepsValidEntriesOnceAndDropsTheRest(): void
    {
        $list = UserAgentList::clean([
            'meta-webindexer',
            'meta-webindexer',
            ' HeadlessChrome ',
            'abc',
            '1234',
            '<script>',
            "bot'quote",
            'line\nbreak',
            "bot\n",
            42,
            null,
            ['nested'],
            str_repeat('a', 201),
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        $this->assertSame([
            'meta-webindexer',
            'HeadlessChrome',
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ], $list);
    }

    public function testAcceptsTheLengthLimits(): void
    {
        $this->assertSame(['abcd'], UserAgentList::clean(['abcd']));
        $this->assertSame([str_repeat('a', 200)], UserAgentList::clean([str_repeat('a', 200)]));
    }

    public function testRejectsCharactersOutsideTheAsciiWordSet(): void
    {
        $this->assertSame([], UserAgentList::clean(['bötbot', 'bot&bot', 'bot"bot', 'bot<bot', 'bot\\bot', "bot\tbot"]));
    }

    public function testTakesAtMostFifty(): void
    {
        $many = array_map(static function (int $i): string {
            return 'bot-' . $i;
        }, range(1, 60));

        $list = UserAgentList::clean($many);
        $this->assertCount(UserAgentList::MAX_ENTRIES, $list);
        $this->assertSame('bot-50', end($list));
    }

    public function testAnythingButAListIsEmpty(): void
    {
        $this->assertSame([], UserAgentList::clean(null));
        $this->assertSame([], UserAgentList::clean('meta-webindexer'));
    }
}
