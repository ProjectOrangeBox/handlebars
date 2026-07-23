<?php

declare(strict_types=1);

use orange\handlebars\Handlebars;
use orange\handlebars\exceptions\FileNotFound;
use orange\handlebars\exceptions\InvalidValue;
use orange\handlebars\exceptions\ViewNotFound;
use orange\handlebars\exceptions\PartialNotFound;
use orange\handlebars\exceptions\DirectoryNotFound;
use orange\handlebars\exceptions\Handlebars as HandlebarsException;

final class HandlebarsTest extends unitTestHelper
{
    protected $instance;
    protected string $cacheDir;
    protected string $templateDir;
    protected string $pluginDir;

    protected function setUp(): void
    {
        $this->cacheDir = $this->makeTempDir('hb-cache-');
        $this->templateDir = $this->makeTempDir('hb-tpl-');
        $this->pluginDir = realpath(__DIR__ . '/../support/plugins');

        file_put_contents($this->templateDir . '/welcome.hbs', 'Hello {{name}}!');

        $this->instance = $this->newHandlebars();
    }

    protected function tearDown(): void
    {
        $this->removeTempDir($this->cacheDir);
        $this->removeTempDir($this->templateDir);
    }

    protected function newHandlebars(array $overrides = []): Handlebars
    {
        return new Handlebars(array_replace([
            'cache directory' => $this->cacheDir,
            'templates' => ['welcome' => $this->templateDir . '/welcome.hbs'],
            'forceCompile' => true,
        ], $overrides));
    }

    // ---- construction --------------------------------------------------

    public function testConstructorThrowsWhenCacheDirectoryMissing(): void
    {
        $this->expectException(DirectoryNotFound::class);

        new Handlebars(['cache directory' => sys_get_temp_dir() . '/does-not-exist-' . uniqid()]);
    }

    // ---- rendering -----------------------------------------------------

    public function testRenderStringReplacesVariables(): void
    {
        $this->assertSame('Hello Ada!', $this->instance->renderString('Hello {{name}}!', ['name' => 'Ada']));
    }

    public function testRenderFileTemplate(): void
    {
        $this->assertSame('Hello Grace!', $this->instance->render('welcome', ['name' => 'Grace']));
    }

    public function testRenderEscapesHtmlByDefault(): void
    {
        $this->assertSame('&lt;b&gt;', $this->instance->renderString('{{value}}', ['value' => '<b>']));
    }

    public function testRenderTripleStacheDoesNotEscape(): void
    {
        $this->assertSame('<b>', $this->instance->renderString('{{{value}}}', ['value' => '<b>']));
    }

    // ---- views ---------------------------------------------------------

    public function testViewExistsAndAddView(): void
    {
        $this->assertTrue($this->instance->viewExists('welcome'));
        $this->assertFalse($this->instance->viewExists('missing'));

        file_put_contents($this->templateDir . '/extra.hbs', 'Extra {{x}}');
        $this->instance->addView('extra', $this->templateDir . '/extra.hbs');

        $this->assertTrue($this->instance->viewExists('extra'));
        $this->assertSame('Extra 1', $this->instance->render('extra', ['x' => 1]));
    }

    public function testFindViewThrowsWhenMissing(): void
    {
        $this->expectException(ViewNotFound::class);

        $this->instance->findView('nope');
    }

    // ---- partials ------------------------------------------------------

    public function testInlineStringPartialIsRendered(): void
    {
        // this is the README example that used to break (resolver treated the
        // string as a file path); an inline partial string must now render
        $this->instance->addPartial('footer', '<footer>{{year}}</footer>');

        $this->assertSame(
            'Top <footer>2026</footer>',
            $this->instance->renderString('Top {{> footer}}', ['year' => 2026])
        );
    }

    public function testFilePathPartialIsRendered(): void
    {
        $partialFile = $this->templateDir . '/nav.hbs';
        file_put_contents($partialFile, '<nav>{{label}}</nav>');

        $this->instance->addPartial('nav', $partialFile);

        $this->assertSame(
            '<nav>Home</nav>',
            $this->instance->renderString('{{> nav}}', ['label' => 'Home'])
        );
    }

    public function testMissingPartialRendersComment(): void
    {
        $out = $this->instance->renderString('{{> ghost}}', []);

        $this->assertStringContainsString('partial named "ghost" could not be found', $out);
    }

    public function testFindPartialThrowsWhenMissing(): void
    {
        $this->expectException(PartialNotFound::class);

        $this->instance->findPartial('nope');
    }

    // ---- helpers -------------------------------------------------------

    public function testValueHelperFromConfig(): void
    {
        $hb = $this->newHandlebars(['helpers' => [$this->pluginDir . '/exclaim.hbs.php']]);

        $this->assertSame('Hi!', $hb->renderString('{{exclaim name}}', ['name' => 'Hi']));
    }

    public function testBlockHelperFromConfig(): void
    {
        $hb = $this->newHandlebars(['helpers' => [$this->pluginDir . '/shout.hbs.php']]);

        $this->assertSame('WORLD', $hb->renderString('{{#shout}}{{name}}{{/shout}}', ['name' => 'world']));
    }

    public function testBundledIfEqHelper(): void
    {
        $hb = $this->newHandlebars(['helpers' => [realpath(__DIR__ . '/../../src/hbsPlugins/if_eq.hbs.php')]]);

        $tpl = '{{#if_eq a b}}same{{else}}different{{/if_eq}}';

        $this->assertSame('same', $hb->renderString($tpl, ['a' => 1, 'b' => 1]));
        $this->assertSame('different', $hb->renderString($tpl, ['a' => 1, 'b' => 2]));
    }

    public function testAddHelperAtRuntime(): void
    {
        // no helper yet -> unknown mustache renders empty
        $this->instance->addHelper($this->pluginDir . '/exclaim.hbs.php');

        $this->assertSame('Yo!', $this->instance->renderString('{{exclaim word}}', ['word' => 'Yo']));
    }

    // ---- change() ------------------------------------------------------

    public function testChangeUpdatesPropertyAndReturnsSelf(): void
    {
        $returned = $this->instance->change('forceCompile', false);

        $this->assertSame($this->instance, $returned);
        $this->assertFalse($this->getPrivatePublic('forceCompile'));
    }

    public function testChangeThrowsOnUnknownName(): void
    {
        $this->expectException(InvalidValue::class);

        $this->instance->change('bogus', 'x');
    }

    public function testChangeThrowsOnWrongType(): void
    {
        $this->expectException(InvalidValue::class);

        // forceCompile expects a bool
        $this->instance->change('forceCompile', 'not-a-bool');
    }

    // ---- run() error handling -----------------------------------------

    public function testRunThrowsFileNotFoundForMissingCompiledFile(): void
    {
        $this->expectException(FileNotFound::class);

        $this->instance->run($this->cacheDir . '/missing-compiled.php', []);
    }

    public function testRunWrapsTemplateRuntimeErrors(): void
    {
        // a compiled template whose closure throws must be wrapped, never
        // echoed/exited (the old behavior called exit(1) from the library)
        $compiled = $this->cacheDir . '/throwing.php';
        file_put_contents($compiled, '<?php return function ($data) { throw new \RuntimeException("kaboom"); };');

        $this->expectException(HandlebarsException::class);

        $this->instance->run($compiled, []);
    }

    public function testRunThrowsWhenCompiledFileIsNotCallable(): void
    {
        $compiled = $this->cacheDir . '/not-callable.php';
        file_put_contents($compiled, '<?php return "just a string";');

        $this->expectException(HandlebarsException::class);

        $this->instance->run($compiled, []);
    }

    // ---- caching -------------------------------------------------------

    public function testCacheRecompilesWhenSourceFileChanges(): void
    {
        $hb = $this->newHandlebars(['forceCompile' => false]);

        $this->assertSame('Hello Ada!', $hb->render('welcome', ['name' => 'Ada']));

        // change the source and make sure its mtime is strictly newer
        file_put_contents($this->templateDir . '/welcome.hbs', 'Goodbye {{name}}!');
        touch($this->templateDir . '/welcome.hbs', time() + 100);

        $this->assertSame('Goodbye Ada!', $hb->render('welcome', ['name' => 'Ada']));
    }

    public function testCompiledFileIsWrittenToCacheDirectory(): void
    {
        $this->instance->renderString('cached {{x}}', ['x' => 1]);

        $compiled = glob($this->cacheDir . '/hbs.*.php');

        $this->assertNotEmpty($compiled, 'a compiled template file should exist in the cache directory');
    }
}
