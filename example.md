# Handlebars — Examples

A hands-on tour of the `orange/handlebars` package. Every snippet below is exercised by the
runnable script at [`examples/example.php`](examples/example.php) and by the unit tests in
[`unittest/tests/`](unittest/tests/).

- [Setup](#setup)
- [Rendering strings and files](#rendering-strings-and-files)
- [Escaping](#escaping)
- [Sections, loops and conditionals](#sections-loops-and-conditionals)
- [Partials](#partials)
- [Helpers (plugins)](#helpers-plugins)
- [Bundled helper catalogue](#bundled-helper-catalogue)
- [Writing your own helper](#writing-your-own-helper)
- [Caching](#caching)
- [Changing settings at runtime](#changing-settings-at-runtime)
- [Framework integration](#framework-integration)

## Setup

`Handlebars` is standalone — it only needs a **cache directory that already exists**.

```php
use orange\handlebars\Handlebars;

$cache = sys_get_temp_dir() . '/hbs';
is_dir($cache) || mkdir($cache, 0777, true);

$hb = new Handlebars([
    'cache directory' => $cache,
    'forceCompile'    => true, // recompile every render while developing
]);
```

## Rendering strings and files

```php
// a raw string
echo $hb->renderString('Hello {{name}}!', ['name' => 'Ada']);
// => Hello Ada!

// a named .hbs file registered up front...
$hb = new Handlebars([
    'cache directory' => $cache,
    'templates'       => ['welcome' => __DIR__ . '/views/welcome.hbs'],
]);
echo $hb->render('welcome', ['name' => 'Grace']);

// ...or added later
$hb->addView('dashboard', __DIR__ . '/views/dashboard.hbs');
echo $hb->render('dashboard', ['user' => 'Grace']);
```

## Escaping

`{{ }}` HTML-escapes; `{{{ }}}` (triple-stache) outputs raw:

```php
echo $hb->renderString('{{value}}',   ['value' => '<b>']); // => &lt;b&gt;
echo $hb->renderString('{{{value}}}', ['value' => '<b>']); // => <b>
```

## Sections, loops and conditionals

Standard Handlebars/Mustache blocks work out of the box:

```handlebars
{{#if user}}Hi {{user.name}}{{/if}}

{{#each items}}
  - {{this}}
{{/each}}

{{#unless empty}}has content{{/unless}}
```

```php
echo $hb->renderString(
    '{{#each items}}[{{this}}]{{/each}}',
    ['items' => ['a', 'b', 'c']]
);
// => [a][b][c]
```

## Partials

A partial value may be a **literal template string** or an **absolute path to a `.hbs` file** —
both are resolved automatically.

```php
$hb->addPartial('footer', '<footer>{{year}}</footer>');   // inline string
$hb->addPartial('header', __DIR__ . '/views/header.hbs');  // file path

echo $hb->renderString('{{> header}}...{{> footer}}', ['year' => 2026]);
```

A missing partial renders an HTML comment instead of throwing:

```php
echo $hb->renderString('{{> ghost}}', []);
// => <!-- partial named "ghost" could not be found -->
```

## Helpers (plugins)

Helpers are enabled by pointing at their file path — either in config or via `addHelper()`:

```php
$hb = new Handlebars([
    'cache directory' => $cache,
    'helpers' => [
        __DIR__ . '/../src/hbsPlugins/format:number.hbs.php',
        __DIR__ . '/../src/hbsPlugins/truncate.hbs.php',
    ],
]);

// or at runtime (rebuilds the helper cache):
$hb->addHelper(__DIR__ . '/../src/hbsPlugins/json.hbs.php');
```

## Bundled helper catalogue

All of these live in [`src/hbsPlugins/`](src/hbsPlugins/). Value helpers emit a string; block
helpers wrap `{{#name}}…{{else}}…{{/name}}` content.

| Template                                                   | Data                             | Output              |
| ---------------------------------------------------------- | -------------------------------- | ------------------- |
| `{{default title "Untitled"}}`                             | `title => ''`                    | `Untitled`          |
| `{{json u}}`                                               | `u => ['a' => 1]`                | `{"a":1}`           |
| `{{join tags ", "}}`                                       | `tags => ['a','b','c']`          | `a, b, c`           |
| `{{length items}}`                                         | `items => [1,2,3,4]`             | `4`                 |
| `{{truncate t 5}}`                                         | `t => 'abcdefgh'`                | `abcde…`            |
| `{{nl2br t}}`                                              | `t => "a\nb"`                    | `a<br />\nb`        |
| `{{capitalize w}}`                                         | `w => 'hello'`                   | `Hello`             |
| `{{url_encode q}}`                                         | `q => 'a b&c'`                   | `a%20b%26c`         |
| `{{format:number n decimals=2}}`                           | `n => 1234.5`                    | `1,234.50`          |
| `{{format:bytes b}}`                                       | `b => 1048576`                   | `1 MB`              |
| `{{format:date ts format="Y"}}`                            | `ts => 1893456000`              | `2030`              |
| `{{#exp:uppercase}}{{w}}{{/exp:uppercase}}`                | `w => 'hi'`                      | `HI`                |
| `{{#exp:lowercase}}{{w}}{{/exp:lowercase}}`                | `w => 'HI'`                      | `hi`                |
| `{{#contains roles "admin"}}Y{{else}}N{{/contains}}`       | `roles => ['user','admin']`      | `Y`                 |
| `{{#repeat 3}}{{number}}{{/repeat}}`                       | —                                | `123`               |
| `{{#if_eq a b}}Y{{else}}N{{/if_eq}}`                       | `a => 1, b => 1`                 | `Y`                 |
| `{{#if_gt a b}}Y{{else}}N{{/if_gt}}`                       | `a => 5, b => 2`                 | `Y`                 |
| `{{#iif a ">" b}}Y{{else}}N{{/iif}}`                       | `a => 5, b => 2`                 | `Y`                 |
| `{{#is_even n}}even{{else}}odd{{/is_even}}`                | `n => 4`                         | `even`              |

Notes:

- `json` returns unescaped output (a `SafeString`) — intended for `<script>` blocks, not HTML
  attributes. Add `pretty=true` for indented JSON.
- `nl2br` HTML-escapes the text first, so it is safe to use with `{{ }}`.
- `join`, `default`, `truncate`, `format:number` take their required arguments as shown; optional
  behaviour is controlled with hash arguments (`decimals=`, `ellipsis=`, `precision=`, …).

## Writing your own helper

A helper file registers a closure on `$helpers`. Do **not** add `declare(strict_types=1)` or a
closing `?>` — helper files are concatenated into one cached file, and their opening `<?php` is
stripped automatically.

```php
<?php
// value helper: {{repeat_str word 3}}
$helpers['repeat_str'] = function ($word, $times, $options) {
    return str_repeat((string) $word, (int) $times);
};

// block helper: {{#loud}}text{{/loud}}
$helpers['loud'] = function ($options) {
    return strtoupper($options['fn']($options['_this'])) . '!';
};
```

The `$options` array a block helper receives:

```
$options['fn']($context)       // render the block body with a context
$options['inverse']($context)  // render the {{else}} body
$options['_this']              // the current context
$options['hash']               // key=value arguments, e.g. decimals=2
$options['name']               // the helper's name
```

## Caching

Compiled templates are written to `cache directory` as PHP files. In production
(`forceCompile => false`) a file template is recompiled automatically when its source `.hbs`
changes — the source's modification time is compared against the compiled file's. String
templates are keyed by their content, so a changed string compiles to a new cache entry.

```php
$prod = new Handlebars(['cache directory' => $cache, 'forceCompile' => false]);
$prod->addView('page', '/app/views/page.hbs');
echo $prod->render('page', $data);   // compiles once, then reuses the cache
// edit page.hbs on disk -> the next render recompiles automatically
```

## Changing settings at runtime

`change()` is type-checked and chainable:

```php
$hb->change('forceCompile', true)
   ->change('extension', '.html')
   ->change('delimiters', ['[[', ']]']);
```

Passing an unknown name, or a value of the wrong type, throws
`orange\handlebars\exceptions\InvalidValue`.

## Framework integration

Inside an Orange application use `HandlebarsView`, which implements the framework's
`ViewInterface`. Register it as the `view` service (typically in `config/services.php`) and give
it your handlebars template directories:

```php
// config/view.php (merged into the HandlebarsView config)
return [
    'template directories' => [__ROOT__ . '/application/*/views'],
    'template extension'   => '.hbs',
    // 'cache directory' defaults to the framework's temp directory when omitted
];
```

```php
// in a controller
echo $this->view->render('main/index', ['name' => 'Ada']);
```

`HandlebarsView` locates the `.hbs` file through the framework's `DirectorySearch`, merges the
view-level data, and delegates rendering to the standalone `Handlebars` engine described above.
