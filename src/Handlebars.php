<?php

declare(strict_types=1);

namespace orange\handlebars;

use Throwable;
use LightnCandy\LightnCandy;
use orange\handlebars\exceptions\FileNotFound;
use orange\handlebars\exceptions\InvalidValue;
use orange\handlebars\exceptions\ViewNotFound;
use orange\handlebars\exceptions\PartialNotFound;
use orange\handlebars\exceptions\DirectoryNotFound;
use orange\handlebars\exceptions\Handlebars as ExceptionsHandlebars;

/**
 * Handlebars Parser
 *
 * This should be pretty standalone
 * use a wrapper to adapt it into your framework
 *
 * Helpers:
 *
 * $helpers['foobar'] = function($options) {};
 *
 * $options =>
 *   [name] => lex_lowercase # helper name
 *   [hash] => Array # key value pair
 *     [size] => 123
 *     [fullname] => Don Myers
 *   [contexts] => ... # full context as object
 *   [_this] => Array # current loop context
 *     [name] => John
 *     [phone] => 933.1232
 *     [age] => 21
 *   ['fn']($options['_this']) # if ??? - don't forget to send in the context
 *   ['inverse']($options['_this']) # else ???- don't forget to send in the context
 *
 */

class Handlebars
{
    /** @var array<string, mixed> */
    protected array $config;

    // these are passed as COMPLETE arrays
    // if it's not in here it doesn't exist
    /** @var array<string, string> view name => absolute path */
    protected array $templates;
    /** @var array<string, string> partial name => absolute path or source */
    protected array $partials;
    /** @var array<string, callable> helper name => the closure implementing it */
    protected array $helpers;

    protected string $cacheDirectory;
    /** @var array<array-key, string> */
    protected array $delimiters;
    protected int $flags;
    protected bool $forceCompile;
    protected string $hbCachePrefix;
    protected string $extension;

    /** @var array<string, callable-string> property name => the is_* function that validates it */
    protected array $changeable = [
        'templates' => 'is_array',
        'partials' => 'is_array',
        'helpers' => 'is_array',
        'cacheDirectory' => 'is_string',
        'delimiters' => 'is_array',
        'flags' => 'is_int',
        'forceCompile' => 'is_bool',
        'hbCachePrefix' => 'is_string',
        'extension' => 'is_string',
    ];

    /**
     * Constructor - Sets Handlebars Preferences
     *
     * The constructor can be passed an array of config values
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace(include __DIR__ . '/config/handlebars.php', $config);

        $this->cacheDirectory = $this->config['cache directory'];
        $this->templates = $this->config['templates'];
        $this->partials = $this->config['partials'];
        $this->forceCompile = $this->config['forceCompile'];
        $this->hbCachePrefix = $this->config['hbCachePrefix'];
        $this->delimiters = $this->config['delimiters'];
        $this->helpers = $this->config['helpers'];
        $this->flags = $this->config['flags'];
        $this->extension = $this->config['extension'];

        if (!is_dir($this->cacheDirectory)) {
            throw new DirectoryNotFound($this->cacheDirectory);
        }

        // we need the "compiled" helpers
        // this loads all of the helpers in the helpers array and builds a single file
        // handlebars can use. Handlebars only includes the helpers used.
        $this->helpers = new HandlebarsPluginCacher($this->config)->get();
    }

    public function change(string $name, mixed $value): self
    {
        // $changeable maps a property name => the validator function for its value
        if (!array_key_exists($name, $this->changeable)) {
            throw new InvalidValue($name);
        }

        $validator = $this->changeable[$name];

        if (!$validator($value)) {
            throw new InvalidValue($name);
        }

        $this->$name = $value;

        return $this;
    }

    /**
     * Parse a template
     *
     * Parses pseudo-variables contained in the specified template view,
     * replacing them with the data in the second param
     *
     * @param array<string, mixed> $data
     */
    public function render(string $view = '', array $data = []): string
    {
        return $this->run($this->parseView($view, true), $data);
    }

    /**
     * Parse a String
     *
     * Parses pseudo-variables contained in the specified string,
     * replacing them with the data in the second param
     *
     * @param array<string, mixed> $data
     */
    public function renderString(string $string, array $data = []): string
    {
        return $this->run($this->parseView($string, false), $data);
    }

    /* handlebars library specific methods */

    /**
     * heavy lifter - wrapper for lightncandy https://github.com/zordius/lightncandy handlebars compiler
     *
     * returns raw compiled_php as string or prepared (executable) php
     */
    public function compile(string $templateSource, string $comment = ''): string
    {
        /* Compile it into php magic! Thank you zordius https://github.com/zordius/lightncandy */
        $compiled = LightnCandy::compile($templateSource, [
            'flags' => $this->flags, /* compiler flags */
            'helpers' => $this->helpers, /* Add the plugins (handlebars.js calls helpers) */
            'renderex' => '/* ' . $comment . ' compiled @ ' . date('Y-m-d h:i:s e') . ' */', /* Added to compiled PHP */
            'delimiters' => $this->delimiters,
            'partialresolver' => /* partial & template handling */
            fn($context, $name) => ($this->partialExists($name)) ? $this->resolvePartial($name) : '<!-- partial named "' . $name . '" could not be found -->',
        ]);

        // LightnCandy returns false when the template will not compile; this
        // method is declared string, so the alternative is a TypeError that
        // says nothing about the template
        if ($compiled === false) {
            throw new ExceptionsHandlebars('Could not compile template' . ($comment !== '' ? ' "' . $comment . '"' : '') . '.');
        }

        return $compiled;
    }

    /**
     * Resolve a registered partial to its template source.
     *
     * A partial value may be either a literal template string (e.g.
     * "<footer>{{year}}</footer>") or an absolute path to a .hbs file. If the
     * value points at an existing file it is read from disk, otherwise it is
     * treated as the template source itself.
     */
    protected function resolvePartial(string $name): string
    {
        $partial = $this->findPartial($name);

        if (!is_file($partial)) {
            return $partial;
        }

        // an unreadable partial file is a broken template, not an empty one
        $source = file_get_contents($partial);

        if ($source === false) {
            throw new ExceptionsHandlebars('Could not read partial "' . $partial . '".');
        }

        return $source;
    }

    public function addView(string $name, string $filePath): self
    {
        $this->templates[$name] = $filePath;

        return $this;
    }

    public function findView(string $name): string
    {
        if (!isset($this->templates[$name])) {
            throw new ViewNotFound($name);
        }

        return $this->templates[$name];
    }

    public function viewExists(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    /* a partial is either a literal template string or an absolute path to a .hbs file */
    public function addPartial(string $name, string $string): self
    {
        $this->partials[$name] = $string;

        return $this;
    }

    public function findPartial(string $name): string
    {
        if (!isset($this->partials[$name])) {
            throw new PartialNotFound($name);
        }

        return $this->partials[$name];
    }

    public function partialExists(string $name): bool
    {
        return isset($this->partials[$name]);
    }

    public function addHelper(string $absFilePath): self
    {
        $this->config['helpers'][] = $absFilePath;

        $config = $this->config;

        $config['forceCompile'] = true;

        $this->helpers = new HandlebarsPluginCacher($config)->get();

        return $this;
    }

    /**
     * save a compiled file
     */
    public function saveCompileFile(string $compiledFile, string $templatePhp): int|false
    {
        /* write out the compiled file */
        return file_put_contents_atomic($compiledFile, '<?php ' . $templatePhp . '?>');
    }

    /**
     * parseTemplate
     *
     * Compiles the template to a cached PHP file and returns that file's path.
     * The cache is (re)built when forceCompile is on, when the compiled file is
     * missing, or - for file templates - when the source .hbs file has been
     * modified since it was last compiled.
     */
    public function parseView(string $template, bool $isFile): string
    {
        /* build the compiled file path (discriminate files from strings so a
           string whose content equals a view name can't collide with it) */
        $prefix = $isFile ? 'file:' : 'string:';
        $compiledFile = $this->cacheDirectory . '/' . $this->hbCachePrefix . sha1($prefix . $template) . '.php';

        $sourceFile = $isFile ? $this->findView($template) : null;

        if ($this->needsCompile($compiledFile, $sourceFile)) {
            /* compile the template as either file or string */
            if ($isFile) {
                // $sourceFile is only null when $isFile is false, but nothing
                // ties the two together for the reader or the analyser
                if ($sourceFile === null) {
                    throw new ExceptionsHandlebars('Could not locate view "' . $template . '".');
                }

                $source = file_get_contents($sourceFile);

                if ($source === false) {
                    throw new ExceptionsHandlebars('Could not read view "' . $sourceFile . '".');
                }

                $comment = $template;
            } else {
                $source = $template;
                $comment = 'parseString_' . sha1($template);
            }

            if ($this->saveCompileFile($compiledFile, $this->compile($source, $comment)) === false) {
                throw new ExceptionsHandlebars('Could not write compiled template to "' . $compiledFile . '".');
            }
        }

        return $compiledFile;
    }

    /**
     * Decide whether a template needs (re)compiling.
     */
    protected function needsCompile(string $compiledFile, ?string $sourceFile): bool
    {
        /* always compile in development, or when no cache exists yet */
        if ($this->forceCompile || !file_exists($compiledFile)) {
            return true;
        }

        /* recompile when the source file has changed since it was last compiled */
        return $sourceFile !== null && filemtime($sourceFile) > filemtime($compiledFile);
    }

    /**
     * run
     *
     * @param array<string, mixed> $data
     */
    public function run(string $compiledFile, array $data): string
    {
        /* did we find this template? */
        if (!file_exists($compiledFile)) {
            /* nope! - fatal error! */
            throw new FileNotFound($compiledFile);
        }

        /* yes include it */
        $templatePHP = include $compiledFile;

        /* is what we loaded even executable? */
        if (!is_callable($templatePHP)) {
            throw new ExceptionsHandlebars();
        }

        /* send data into the magic void... */
        try {
            $output = $templatePHP($data);
        } catch (Throwable $e) {
            /* log when running inside the framework, but never echo/exit from a
               library - wrap the low-level error and let the caller handle it */
            if (function_exists('logMsg')) {
                logMsg('error', __METHOD__ . ': ' . $e->getMessage());
            }

            throw new ExceptionsHandlebars('Handlebars run error: ' . $e->getMessage(), 0, $e);
        }

        return $output;
    }
}
