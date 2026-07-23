# Handlebars

Standalone Handlebars template renderer built on [zordius/lightncandy](https://github.com/zordius/lightncandy). Compiles `.hbs` templates (or raw strings) to cached PHP, then executes them against your data. `HandlebarsView` adapts it to Orange Framework's `ViewInterface` for use inside an application; `Handlebars` itself has no framework dependency.

See [example.md](example.md) for a full tour, and [`examples/example.php`](examples/example.php) for a runnable script.

## Install

```bash
composer require orange/handlebars
```

## Example

```php
use orange\handlebars\Handlebars;

$handlebars = new Handlebars([
    'cache directory' => '/tmp/handlebars-cache', // must already exist
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

// a partial value may be a literal template string...
$handlebars->addPartial('footer', '<footer>{{year}}</footer>');
// ...or an absolute path to a .hbs file - both are resolved automatically
$handlebars->addPartial('header', '/app/views/partials/header.hbs');

$handlebars->addHelper('/app/helpers/uppercase.hbs.php'); // recompiles the helper cache
```

```handlebars
{{! use a partial }}
{{> footer}}
```

## Configuration

Defaults live in [`src/config/handlebars.php`](src/config/handlebars.php); anything you pass to the constructor overrides them.

| Key               | Type   | Default                    | Purpose                                                        |
| ----------------- | ------ | -------------------------- | -------------------------------------------------------------- |
| `cache directory` | string | `__ROOT__/var/handlebars`  | Where compiled templates and the helper cache are written. **Must exist.** |
| `templates`       | array  | `[]`                       | `name => absolute .hbs path` map for `render()`.               |
| `partials`        | array  | `[]`                       | `name => template string or .hbs path` map.                   |
| `helpers`         | array  | `[]`                       | List of absolute paths to helper (plugin) files.               |
| `forceCompile`    | bool   | `DEBUG`                    | Recompile on every render (turn on in development).            |
| `extension`       | string | `.hbs`                     | Template file extension.                                       |
| `hbCachePrefix`   | string | `hbs.`                     | Filename prefix for compiled templates.                       |
| `delimiters`      | array  | `['{{', '}}']`             | Mustache delimiters.                                          |
| `flags`           | int    | LightnCandy flags          | Compiler flags (see the LightnCandy docs).                    |

### Caching

Compiled templates are cached to disk under `cache directory`. With `forceCompile` off (production), a file template is automatically recompiled when its source `.hbs` file changes (compared by modification time), so edits are never silently ignored. Turn `forceCompile` on in development to recompile on every render.

## Runtime changes

`change($name, $value)` updates a single setting after construction; the value is type-checked and the call is chainable:

```php
$handlebars
    ->change('forceCompile', true)
    ->change('extension', '.html');
```

Changeable keys: `templates`, `partials`, `helpers`, `cacheDirectory`, `delimiters`, `flags`, `forceCompile`, `hbCachePrefix`, `extension`.

## Helpers (plugins)

A helper is a PHP file that registers a closure on the `$helpers` array. Point the `helpers` config (or `addHelper()`) at its absolute path and it is compiled into a single cached helper file that LightnCandy draws from.

```php
// /app/helpers/uppercase.hbs.php
<?php
$helpers['shout'] = function ($options) {
    return strtoupper($options['fn']($options['_this'])); // block helper
};
```

```handlebars
{{#shout}}{{name}}{{/shout}}
```

### Bundled helpers

Ready-to-use helper files live in [`src/hbsPlugins/`](src/hbsPlugins/). Add the ones you want to the `helpers` config.

| Helper                                          | Kind  | Example                                              |
| ----------------------------------------------- | ----- | ---------------------------------------------------- |
| `default`                                       | value | `{{default title "Untitled"}}`                       |
| `json`                                          | value | `{{json user pretty=true}}`                          |
| `join`                                          | value | `{{join tags ", "}}`                                 |
| `length`                                        | value | `{{length items}}`                                   |
| `truncate`                                      | value | `{{truncate body 120 ellipsis="…"}}`                 |
| `nl2br`                                         | value | `{{nl2br comment}}`                                  |
| `capitalize`                                    | value | `{{capitalize word}}`                                |
| `url_encode`                                    | value | `{{url_encode query}}`                               |
| `format:number`                                 | value | `{{format:number total decimals=2}}`                 |
| `format:bytes`                                  | value | `{{format:bytes size precision=2}}`                  |
| `format:date`                                   | value | `{{format:date entry_date format="Y-m-d"}}`          |
| `uppercase` / `lowercase`                       | block | `{{#exp:uppercase}}{{name}}{{/exp:uppercase}}`       |
| `contains`                                      | block | `{{#contains roles "admin"}}…{{/contains}}`          |
| `repeat`                                        | block | `{{#repeat 3}}{{number}}{{/repeat}}`                 |
| `if_eq` / `if_ne` / `if_gt` / `if_lt`           | block | `{{#if_eq a b}}…{{else}}…{{/if_eq}}`                  |
| `iif`                                           | block | `{{#iif a ">" b}}…{{/iif}}`                           |
| `is_even` / `is_odd`                            | block | `{{#is_even n}}…{{/is_even}}`                         |

See [example.md](example.md) for the full catalogue with output.

## Framework integration

Inside an Orange application, use `HandlebarsView`, which implements the framework's `ViewInterface` (view search paths, data merging, temp-directory caching). Register it as the `view` service and render with `$this->view->render('main/index', $data)`. See [example.md](example.md#framework-integration).

## Tests

```bash
cd unittest && sh runUnitTests.sh          # whole suite
cd unittest && sh runUnitTests.sh PluginsTest   # a single file
```
