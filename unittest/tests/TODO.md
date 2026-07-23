# Test coverage

Covered:

- `HandlebarsTest` — the standalone `Handlebars` engine: string/file rendering, escaping,
  views, partials (inline string + file path + missing), config + runtime helpers, `change()`,
  `run()` error handling, and mtime-based cache invalidation.
- `HandlebarsPluginCacherTest` — helper compilation, missing dir/helper errors, cache reuse.
- `PluginsTest` — renders every deterministic bundled helper (new + existing).

Not covered (needs the framework container/config machinery, not a unit test):

- `HandlebarsView` — the `ViewInterface` adapter. Exercise it through an application/framework
  integration test where the `view` service and its config directory are available.
