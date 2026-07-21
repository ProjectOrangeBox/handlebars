<?php

namespace peels\handlebars;

use LightnCandy\LightnCandy;
use peels\handlebars\exceptions\FileNotFound;
use peels\handlebars\exceptions\InvalidValue;
use peels\handlebars\exceptions\ViewNotFound;
use peels\handlebars\exceptions\PartialNotFound;
use peels\handlebars\exceptions\DirectoryNotFound;
use peels\handlebars\exceptions\Handlebars as ExceptionsHandlebars;

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
    protected array $config;

    // these are passed as COMPLETE arrays
    // if it's not in here it doesn't exist
    protected array $templates;
    protected array $partials;
    protected array $helpers;

    protected string $cacheDirectory;
    protected array $delimiters;
    protected int $flags;
    protected bool $forceCompile;
    protected string $hbCachePrefix;
    protected string $extension;

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
        $this->helpers = (new HandlebarsPluginCacher($this->config))->get();
    }

    public function change(string $name, mixed $value): self
    {
        if (!in_array($name, $this->changeable)) {
            throw new InvalidValue($name);
        }

        $function = str_replace(' ', '', lcfirst(ucwords($this->changeable[$name])));

        if (!$function($value)) {
            throw new InvalidValue($value);
        }

        $this->$name = $value;

        return $this;
    }

    /**
     * Parse a template
     *
     * Parses pseudo-variables contained in the specified template view,
     * replacing them with the data in the second param
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
        return LightnCandy::compile($templateSource, [
            'flags' => $this->flags, /* compiler flags */
            'helpers' => $this->helpers, /* Add the plugins (handlebars.js calls helpers) */
            'renderex' => '/* ' . $comment . ' compiled @ ' . date('Y-m-d h:i:s e') . ' */', /* Added to compiled PHP */
            'delimiters' => $this->delimiters,
            'partialresolver' => function ($context, $name) {
                /* partial & template handling */
                return ($this->partialExists($name)) ? file_get_contents($this->findPartial($name)) : '<!-- partial named "' . $name . '" could not be found --!>';
            },
        ]);
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

    /* a partial is a string */
    public function addPartial(string $name, string $string): Handlebars
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

        $this->helpers = (new HandlebarsPluginCacher($config))->get();

        return $this;
    }

    /**
     * save a compiled file
     */
    public function saveCompileFile(string $compiledFile, string $templatePhp): int
    {
        /* write out the compiled file */
        return file_put_contents_atomic($compiledFile, '<?php ' . $templatePhp . '?>');
    }

    /**
     * parseTemplate
     */
    public function parseView(string $template, bool $isFile): string
    {
        /* build the compiled file path */
        $compiledFile = $this->cacheDirectory . '/' . $this->hbCachePrefix . sha1($template) . '.php';

        /* always compile in development or not save or compile if doesn't exist */
        if ($this->forceCompile || !file_exists($compiledFile)) {
            /* compile the template as either file or string */
            if ($isFile) {
                $source = file_get_contents($this->findView($template));
                $comment = $template;
            } else {
                $source = $template;
                $comment = 'parseString_' . sha1($template);
            }

            $this->saveCompileFile($compiledFile, $this->compile($source, $comment));
        }

        return $compiledFile;
    }

    /**
     * run
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
        } catch (ExceptionsHandlebars $e) {
            echo '<h1>Handlebars Run Error:</h1><pre>';
            var_dump($e);
            logMsg('DEBUG', __METHOD__, $e);
            echo '</pre>';
            exit(1);
        }

        return $output;
    }
}
