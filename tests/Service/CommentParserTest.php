<?php

namespace App\Tests\Service;

use App\Service\CommentParser;
use PHPUnit\Framework\TestCase;

class CommentParserTest extends TestCase
{
    private CommentParser $instance;

    protected function setUp(): void
    {
        $this->instance = new CommentParser();
    }

    /**
     * @dataProvider getData
     *
     * @param callable(array $domains): void $test
     */
    public function testFindFixedLinks(string $text, callable $test): void
    {
        $domains = $this->instance->findFixedLinks($text);
        $test($domains);
    }

    public static function getData(): iterable
    {
        // no link
        yield ['Test', static fn (array $domains) => self::assertCount(0, $domains)];
        // just a link
        yield ['https://lemmings.world/c/a', static function (array $domains): void {
            self::assertCount(1, $domains);
            self::assertContains('!a@lemmings.world', $domains);
        }];
        // with surrounding text
        yield [
            'Surrounding text, https://lemmings.world/c/a another surrounding text https://lemmings.world/c/b',
            static function (array $domains): void {
                self::assertCount(2, $domains);
                self::assertContains('!a@lemmings.world', $domains);
                self::assertContains('!b@lemmings.world', $domains);
            },
        ];
        // full format
        yield ['https://lemmings.world/c/a@lemmy.world', static function (array $domains): void {
            self::assertCount(1, $domains);
            self::assertContains('!a@lemmy.world', $domains);
        }];
        // same community thrice
        yield [
            'https://lemmings.world/c/a https://lemmings.world/c/a https://lemmy.world/c/a@lemmings.world',
            static function (array $domains): void {
                self::assertCount(1, $domains);
                self::assertContains('!a@lemmings.world', $domains);
            },
        ];

        yield [
            'https://lemmings.world/c/a@lemmy.world !a@lemmy.world',
            static fn (array $domains) => self::assertCount(0, $domains),
        ];
    }
}
