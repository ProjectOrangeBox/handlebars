<?php

declare(strict_types=1);

namespace orange\handlebars;

use orange\handlebars\exceptions\HelperNotFound;
use orange\handlebars\exceptions\DirectoryNotFound;

class HandlebarsPluginCacher
{
    protected array $plugins;
    protected string $cacheDirectory;
    protected bool $forceCompile;
    protected array $pluginFiles;

    public function __construct(array $config)
    {
        // fall back to globals only when they exist so this stays usable standalone
        $this->cacheDirectory = $config['cache directory'] ?? (defined('__ROOT__') ? __ROOT__ . '/var' : sys_get_temp_dir());
        $this->forceCompile = $config['forceCompile'] ?? (defined('DEBUG') ? DEBUG : false);
        $this->pluginFiles = $config['helpers'] ?? [];

        if (!is_dir($this->cacheDirectory)) {
            throw new DirectoryNotFound($this->cacheDirectory);
        }

        $cacheFile = rtrim($this->cacheDirectory, '/') . '/cached.helpers.php';

        if ($this->needsCompile($cacheFile)) {
            $combined  = '<?php' . PHP_EOL . '/*' . PHP_EOL . 'DO NOT MODIFY THIS FILE' . PHP_EOL . 'Written: ' . date('Y-m-d H:i:s T') . PHP_EOL . '*/' . PHP_EOL . PHP_EOL;

            /* find all of the plugin "services" */
            foreach ($this->pluginFiles as $path) {
                if (!file_exists($path)) {
                    throw new HelperNotFound($path);
                }

                $pluginSource  = php_strip_whitespace($path);
                $pluginSource  = trim(str_replace(['<?php', '<?', '?>'], '', $pluginSource));
                $pluginSource  = trim('/* ' . $path . ' */' . PHP_EOL . $pluginSource) . PHP_EOL . PHP_EOL;

                $combined .= $pluginSource;
            }

            /* save to the cache directory on this machine (in a multi-machine env each will just recreate this locally) */
            file_put_contents_atomic($cacheFile, trim($combined));
        }

        /* start with empty array */
        $helpers = [];

        /* include the combined "cache" file */
        include $cacheFile;

        $this->plugins = $helpers;
    }

    /**
     * (Re)build the combined helper cache when it's stale.
     *
     * Stale means: forceCompile is on, the cache file doesn't exist yet, or any
     * source helper file has been modified since the cache was last written.
     */
    protected function needsCompile(string $cacheFile): bool
    {
        if ($this->forceCompile || !file_exists($cacheFile)) {
            return true;
        }

        $cacheTime = filemtime($cacheFile);
        return array_any($this->pluginFiles, fn($path) => file_exists($path) && filemtime($path) > $cacheTime);
    }

    public function get(): array
    {
        return $this->plugins;
    }
}
