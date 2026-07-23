<?php

declare(strict_types=1);

use orange\handlebars\HandlebarsPluginCacher;
use orange\handlebars\exceptions\HelperNotFound;
use orange\handlebars\exceptions\DirectoryNotFound;

final class HandlebarsPluginCacherTest extends unitTestHelper
{
    protected string $cacheDir;
    protected string $pluginDir;

    protected function setUp(): void
    {
        $this->cacheDir = $this->makeTempDir('hb-plugcache-');
        $this->pluginDir = realpath(__DIR__ . '/../support/plugins');
    }

    protected function tearDown(): void
    {
        $this->removeTempDir($this->cacheDir);
    }

    public function testThrowsWhenCacheDirectoryMissing(): void
    {
        $this->expectException(DirectoryNotFound::class);

        new HandlebarsPluginCacher([
            'cache directory' => sys_get_temp_dir() . '/nope-' . uniqid(),
            'helpers' => [],
        ]);
    }

    public function testThrowsWhenHelperFileMissing(): void
    {
        $this->expectException(HelperNotFound::class);

        new HandlebarsPluginCacher([
            'cache directory' => $this->cacheDir,
            'forceCompile' => true,
            'helpers' => [$this->pluginDir . '/no-such-plugin.hbs.php'],
        ]);
    }

    public function testGetReturnsCompiledHelperClosures(): void
    {
        $cacher = new HandlebarsPluginCacher([
            'cache directory' => $this->cacheDir,
            'forceCompile' => true,
            'helpers' => [
                $this->pluginDir . '/exclaim.hbs.php',
                $this->pluginDir . '/shout.hbs.php',
            ],
        ]);

        $helpers = $cacher->get();

        $this->assertIsArray($helpers);
        $this->assertArrayHasKey('exclaim', $helpers);
        $this->assertArrayHasKey('shout', $helpers);
        $this->assertInstanceOf(\Closure::class, $helpers['exclaim']);
    }

    public function testWritesCombinedCacheFile(): void
    {
        new HandlebarsPluginCacher([
            'cache directory' => $this->cacheDir,
            'forceCompile' => true,
            'helpers' => [$this->pluginDir . '/exclaim.hbs.php'],
        ]);

        $this->assertFileExists($this->cacheDir . '/cached.helpers.php');
    }

    public function testCacheIsReusedWhenNotForcedAndUnchanged(): void
    {
        $config = [
            'cache directory' => $this->cacheDir,
            'forceCompile' => false,
            'helpers' => [$this->pluginDir . '/exclaim.hbs.php'],
        ];

        new HandlebarsPluginCacher($config);

        $cacheFile = $this->cacheDir . '/cached.helpers.php';
        $firstWrite = filemtime($cacheFile);

        // rebuild with the same (unchanged) inputs - cache must not be rewritten
        clearstatcache();
        new HandlebarsPluginCacher($config);

        $this->assertSame($firstWrite, filemtime($cacheFile));
    }
}
