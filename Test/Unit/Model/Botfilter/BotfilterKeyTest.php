<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\Botfilter;

use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\Botfilter\BotfilterKey;

class BotfilterKeyTest extends TestCase
{
    private const CONTAINER = '5460b7ac-f1df-4aa8-a41c-08b98ebcf14d';

    public function testParsesContainerAndToken(): void
    {
        $key = BotfilterKey::parse('  ' . strtoupper(self::CONTAINER) . ':bfs_abcDEF123_-xyz0123456789  ');
        $this->assertNotNull($key);
        $this->assertSame(self::CONTAINER, $key->getContainerId());
        $this->assertSame('bfs_abcDEF123_-xyz0123456789', $key->getToken());
    }

    /** @dataProvider invalidKeys */
    public function testRejectsWhatIsNotAKey(?string $value): void
    {
        $this->assertNull(BotfilterKey::parse($value));
    }

    public function invalidKeys(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'only the token' => ['bfs_abcDEF123_-xyz0123456789'],
            'the server container token' => [self::CONTAINER . ':bf_abcDEF123_-xyz0123456789'],
            'token too short' => [self::CONTAINER . ':bfs_short'],
            'an encrypted value' => ['0:3:c29tZXRoaW5nIGVuY3J5cHRlZA=='],
            'extra text' => [self::CONTAINER . ':bfs_abcDEF123_-xyz0123456789 extra'],
        ];
    }
}
