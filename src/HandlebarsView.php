<?php

declare(strict_types=1);

namespace orange\handlebars;

use orange\framework\abstract\ViewAbstract;
use orange\handlebars\exceptions\ViewNotFound;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;

class HandlebarsView extends ViewAbstract implements ViewInterface
{
    protected Handlebars $handlebars;

    protected function __construct(array $config, ?DataInterface $data = null)
    {
        parent::__construct($config, $data);

        // use the ViewAbstract temp directory for the cache directory if one isn't provided
        if (!isset($this->config['cache directory'])) {
            $this->config['cache directory'] = $this->tempDirectory;
        }

        $this->handlebars = new Handlebars($this->config);
    }

    /**
     * Render a compiled Handlebars template.
     *
     * This used to keep a DirectorySearch of its own, scoped to the configured
     * template directories, because .hbs templates live somewhere other than
     * the PHP views. Finding them is no longer a view engine's business: a
     * caller resolves the path - through a ViewFinder configured for templates,
     * or any other way - and hands it over.
     *
     * Handlebars addresses its templates by name through an internal registry,
     * so the path doubles as the registry key. It is unique by construction and
     * stable across renders, which is all the key has to be.
     *
     * @param string $viewFile Absolute path to the .hbs template
     */
    #[\Override]
    public function render(string $viewFile = '', array $data = [], array $options = []): string
    {
        if (!$this->handlebars->viewExists($viewFile)) {
            if (!is_file($viewFile)) {
                throw new ViewNotFound($viewFile);
            }

            $this->handlebars->addView($viewFile, $viewFile);
        }

        return $this->handlebars->render($viewFile, $this->data($data));
    }

    #[\Override]
    public function renderString(string $string, array $data = [], array $options = []): string
    {
        return $this->handlebars->renderString($string, $this->data($data));
    }

    #[\Override]
    public function change(string $name, mixed $value): self
    {
        $this->handlebars->change($name, $value);

        return $this;
    }
}
