<?php

declare(strict_types=1);

namespace peels\handlebars;

use peels\handlebars\exceptions\HelperNotFound;
use peels\handlebars\exceptions\DirectoryNotFound;

class HandlebarsPluginCacher
{
    protected array $plugins;
    protected string $cacheDirectory;
    protected bool $forceCompile;
    protected array $pluginFiles;

    public function __construct(array $config)
    {
        $this->cacheDirectory = $config['cache directory'] ?? __ROOT__ . '/var';
        $this->forceCompile = $config['forceCompile'] ?? DEBUG;
        $this->pluginFiles = $config['helpers'] ?? [];

        if (!is_dir($this->cacheDirectory)) {
            throw new DirectoryNotFound($this->cacheDirectory);
        }

        $cacheFile = rtrim($this->cacheDirectory, '/') . '/cached.helpers.php';

        if ($this->forceCompile || !file_exists($cacheFile)) {
            $combined  = '<?php' . PHP_EOL . '/*' . PHP_EOL . 'DO NOT MODIFY THIS FILE' . PHP_EOL . 'Written: ' . date('Y-m-d H:i:s T') . PHP_EOL . '*/' . PHP_EOL . PHP_EOL;

            /* find all of the plugin "services" */
            if (is_array($this->pluginFiles)) {
                foreach ($this->pluginFiles as $path) {
                    if (!file_exists($path)) {
                        throw new HelperNotFound($path);
                    }

                    $pluginSource  = php_strip_whitespace($path);
                    $pluginSource  = trim(str_replace(['<?php', '<?', '?>'], '', $pluginSource));
                    $pluginSource  = trim('/* ' . $path . ' */' . PHP_EOL . $pluginSource) . PHP_EOL . PHP_EOL;

                    $combined .= $pluginSource;
                }
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

    public function get()
    {
        return $this->plugins;
    }
}
