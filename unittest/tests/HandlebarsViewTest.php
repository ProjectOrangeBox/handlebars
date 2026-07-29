<?php

declare(strict_types=1);

use orange\handlebars\HandlebarsView;
use orange\handlebars\exceptions\ViewNotFound;

/**
 * HandlebarsView had no test at all, which is how it shipped unconstructible:
 * ConfigurationTrait::determineConfigPath() looks for a config file named after
 * the lowercased short class name, and only "handlebars.php" existed - so every
 * getInstance() threw ConfigFileNotFound. testConstructs() below is the
 * regression test for that; it fails with ConfigFileNotFound without
 * src/config/handlebarsview.php.
 */
final class HandlebarsViewTest extends unitTestHelper
{
    protected string $cacheDirectory;
    protected string $templatePath;

    protected function setUp(): void
    {
        // Handlebars::__construct() throws DirectoryNotFound unless the cache
        // directory already exists, so it cannot be left to the default here
        $this->cacheDirectory = $this->makeTempDir('orange-hbs-');
        $this->templatePath = __DIR__ . '/support/templates';
    }

    protected function tearDown(): void
    {
        $this->removeTempDir($this->cacheDirectory);
    }

    /**
     * newInstance, not getInstance: getInstance() caches per class for the whole
     * process, so every test after the first would reuse the first test's
     * (already removed) cache directory.
     */
    protected function make(array $config = []): HandlebarsView
    {
        return HandlebarsView::newInstance(array_replace([
            'cache directory' => $this->cacheDirectory,
        ], $config));
    }

    /**
     * render() takes a path. A .hbs template lives outside the PHP view tree,
     * so this engine used to keep a DirectorySearch of its own to find one -
     * finding it is now the caller's job, whether that is a ViewFinder
     * configured for templates or a test building the path directly.
     */
    protected function template(string $name): string
    {
        return $this->templatePath . '/' . $name . '.hbs';
    }

    public function testConstructs(): void
    {
        $this->assertInstanceOf(HandlebarsView::class, $this->make());
    }

    public function testRendersATemplateFile(): void
    {
        $this->assertSame("Hello Ada!\n", $this->make()->render($this->template('hello'), ['name' => 'Ada']));
    }

    public function testRendersAString(): void
    {
        $this->assertSame('Hi Grace', $this->make()->renderString('Hi {{name}}', ['name' => 'Grace']));
    }

    public function testThrowsForUnknownView(): void
    {
        $this->expectException(ViewNotFound::class);

        $this->make()->render($this->template('no-such-template'));
    }

    /**
     * The path doubles as the key in Handlebars' own template registry, so
     * rendering the same template twice reuses the registered entry rather than
     * adding it again.
     */
    public function testRendersTheSameTemplateTwice(): void
    {
        $view = $this->make();

        $this->assertSame("Hello Ada!\n", $view->render($this->template('hello'), ['name' => 'Ada']));
        $this->assertSame("Hello Grace!\n", $view->render($this->template('hello'), ['name' => 'Grace']));
    }

    public function testChangeIsForwardedToTheParser(): void
    {
        $view = $this->make();

        $view->change('forceCompile', true);

        $this->assertSame("Hello Ada!\n", $view->render($this->template('hello'), ['name' => 'Ada']));
    }
}
