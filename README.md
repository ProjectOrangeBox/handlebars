# Handlebars

Standalone Handlebars template renderer built on [zordius/lightncandy](https://github.com/zordius/lightncandy). Compiles `.hbs` templates (or raw strings) to cached PHP, then executes them against your data. `HandlebarsView` adapts it to Orange Framework's `ViewInterface` for use inside an application; `Handlebars` itself has no framework dependency.

## Example

```php
use orange\handlebars\Handlebars;

$handlebars = new Handlebars([
    'cache directory' => '/tmp/handlebars-cache',
    'templates' => ['welcome' => '/app/views/welcome.hbs'],
    'partials' => [],
    'helpers' => [],
]);

echo $handlebars->render('welcome', ['name' => 'Ada']);

// or render a string directly, no file needed
echo $handlebars->renderString('Hello {{name}}!', ['name' => 'Ada']);
```

Add templates, partials, and custom helper files after construction:

```php
$handlebars->addView('dashboard', '/app/views/dashboard.hbs');
$handlebars->addPartial('footer', '<footer>{{year}}</footer>');
$handlebars->addHelper('/app/helpers/uppercase.php'); // recompiles the helper cache
```

Compiled templates are cached to disk under `cache directory`; set `forceCompile` to recompile on every render during development.
