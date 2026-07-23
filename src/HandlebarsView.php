<?php

declare(strict_types=1);

namespace orange\handlebars;

use orange\framework\abstract\ViewAbstract;
use orange\framework\helpers\DirectorySearch;
use orange\handlebars\exceptions\ViewNotFound;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\DirectorySearchInterface;

class HandlebarsView extends ViewAbstract implements ViewInterface
{
    protected Handlebars $handlebars;

    protected function __construct(array $config, ?DataInterface $data = null)
    {
        parent::__construct($config, $data, null);

        // use the ViewAbstract temp directory for the cache directory if one isn't provided
        if (!isset($this->config['cache directory'])) {
            $this->config['cache directory'] = $this->tempDirectory;
        }

        // replace the view search with one scoped to our handlebars template directories
        if (isset($this->config['template directories'])) {
            $this->search = new DirectorySearch([
                'directories' => $this->config['template directories'],
                'extension' => $this->config['template extension'] ?? $this->config['extension'] ?? '.hbs',
                'recursive' => true,
                'pend' => DirectorySearchInterface::FIRST,
            ]);
        }

        $this->handlebars = new Handlebars($this->config);
    }

    #[\Override]
    public function render(string $view = '', array $data = [], array $options = []): string
    {
        if (!$this->handlebars->viewExists($view)) {
            // let's see if we can locate it and add it!
            $foundView = $this->search->findFirst($view);

            if ($foundView) {
                $this->handlebars->addView($view, $foundView);
            } else {
                throw new ViewNotFound($view);
            }
        }

        return $this->handlebars->render($view, $this->data($data));
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
