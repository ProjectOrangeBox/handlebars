<?php

declare(strict_types=1);

use orange\handlebars\Handlebars;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Renders every bundled helper we can exercise deterministically, proving the
 * plugin file loads through HandlebarsPluginCacher and behaves as documented.
 */
final class PluginsTest extends unitTestHelper
{
    protected $instance;
    protected string $cacheDir;

    /** helpers that are dependency-free and produce deterministic output */
    protected array $pluginFiles = [
        // new helpers
        'default', 'json', 'join', 'length', 'truncate', 'nl2br', 'capitalize',
        'format:number', 'format:bytes', 'contains', 'repeat', 'url_encode',
        // existing helpers
        'if_eq', 'if_ne', 'if_gt', 'if_lt', 'iff', 'is_even', 'is_odd',
        'uppercase', 'lowercase', 'format:date',
    ];

    protected function setUp(): void
    {
        $this->cacheDir = $this->makeTempDir('hb-plugins-');

        $pluginDir = realpath(__DIR__ . '/../../src/hbsPlugins') . '/';
        $paths = array_map(fn ($name) => $pluginDir . $name . '.hbs.php', $this->pluginFiles);

        $this->instance = new Handlebars([
            'cache directory' => $this->cacheDir,
            'forceCompile' => true,
            'helpers' => $paths,
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir($this->cacheDir);
    }

    #[DataProvider('pluginProvider')]
    public function testPluginRenders(string $template, array $data, string $expected): void
    {
        $this->assertSame($expected, $this->instance->renderString($template, $data));
    }

    public static function pluginProvider(): array
    {
        return [
            // new helpers
            'default falls back'      => ['{{default title "Untitled"}}', ['title' => ''], 'Untitled'],
            'default keeps value'     => ['{{default title "Untitled"}}', ['title' => 'Hi'], 'Hi'],
            'json encodes'            => ['{{json u}}', ['u' => ['a' => 1]], '{"a":1}'],
            'join with glue'          => ['{{join t ", "}}', ['t' => ['a', 'b', 'c']], 'a, b, c'],
            'length of array'         => ['{{length i}}', ['i' => [1, 2, 3, 4]], '4'],
            'length of string'        => ['{{length s}}', ['s' => 'hello'], '5'],
            'truncate cuts'           => ['{{truncate t 5}}', ['t' => 'abcdefgh'], 'abcde…'],
            'truncate keeps short'    => ['{{truncate t 50}}', ['t' => 'short'], 'short'],
            'nl2br'                   => ['{{nl2br t}}', ['t' => "a\nb"], "a<br />\nb"],
            'capitalize'              => ['{{capitalize w}}', ['w' => 'hello'], 'Hello'],
            'format number'          => ['{{format:number n decimals=2}}', ['n' => 1234.5], '1,234.50'],
            'format bytes'            => ['{{format:bytes b}}', ['b' => 1048576], '1 MB'],
            'contains matches'        => ['{{#contains r "admin"}}Y{{else}}N{{/contains}}', ['r' => ['user', 'admin']], 'Y'],
            'contains no match'       => ['{{#contains r "admin"}}Y{{else}}N{{/contains}}', ['r' => ['user']], 'N'],
            'repeat'                  => ['{{#repeat 3}}{{number}}{{/repeat}}', [], '123'],
            'url_encode'              => ['{{url_encode q}}', ['q' => 'a b&c'], 'a%20b%26c'],
            // existing helpers
            'if_eq true'              => ['{{#if_eq a b}}Y{{else}}N{{/if_eq}}', ['a' => 1, 'b' => 1], 'Y'],
            'if_eq false'             => ['{{#if_eq a b}}Y{{else}}N{{/if_eq}}', ['a' => 1, 'b' => 2], 'N'],
            'if_ne'                   => ['{{#if_ne a b}}Y{{else}}N{{/if_ne}}', ['a' => 1, 'b' => 2], 'Y'],
            'if_gt'                   => ['{{#if_gt a b}}Y{{else}}N{{/if_gt}}', ['a' => 5, 'b' => 2], 'Y'],
            'if_lt'                   => ['{{#if_lt a b}}Y{{else}}N{{/if_lt}}', ['a' => 1, 'b' => 2], 'Y'],
            'iff equals'              => ['{{#iif a "=" b}}Y{{else}}N{{/iif}}', ['a' => 3, 'b' => 3], 'Y'],
            'is_even'                 => ['{{#is_even n}}even{{else}}odd{{/is_even}}', ['n' => 4], 'even'],
            'is_odd'                  => ['{{#is_odd n}}odd{{else}}even{{/is_odd}}', ['n' => 3], 'odd'],
            'uppercase block'         => ['{{#exp:uppercase}}{{w}}{{/exp:uppercase}}', ['w' => 'hi'], 'HI'],
            'lowercase block'         => ['{{#exp:lowercase}}{{w}}{{/exp:lowercase}}', ['w' => 'HI'], 'hi'],
            'format date'             => ['{{format:date ts format="Y"}}', ['ts' => 1893456000], '2030'],
        ];
    }
}
