# real3d-flipbook-woocommerce-addon

WordPress plugin that gates Real3D Flipbook flipbooks behind WooCommerce
purchases and subscriptions. Buyers get the full flipbook; everyone else gets the
preview. Tooling comes from WP Preset (see that repo's README for the full
reference).

It depends on two plugins at runtime. WooCommerce is free. Real3D Flipbook is a
commercial CodeCanyon plugin, so neither wp-env nor CI has it (see Tests below).

It ships through GitHub releases and
[plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker),
not wordpress.org. There's no `readme.txt` and that's on purpose.

## Commands

```bash
composer phpcbf            # autofix PHP
composer phpcs             # report what phpcbf could not fix
composer phpstan           # static analysis, level 8, no baseline
composer lint              # phpcs + phpstan
composer test              # unit tests: fast, no WordPress
composer test:integration  # integration tests: run inside wp-env, see below
composer build             # package dist/<slug>-<version>.zip
npm run lint:js            # ESLint
npm run lint:css           # Stylelint
npm run format             # Prettier
npm run env:start          # local WordPress with WooCommerce
```

There's no `src/` and nothing for `npm run build` to compile. The JS and CSS in
`js/` and `css/` ship as written.

## Code layout

- `real3d-flipbook-woocommerce-addon.php` defines `R3D_WOO_VERSION` and
  `R3D_WOO_FILE`, boots plugin-update-checker, then creates the `R3D_Woo`
  singleton.
- `includes/main.php` holds all of `R3D_Woo`. It's a global class in a WordPress
  style file, required directly. Nothing is Composer autoloaded and the plugin
  never loads `vendor/autoload.php`, so a new class needs its own `require_once`
  in the entry file.
- `lib/plugin-update-checker/` is vendored third-party code, including its own
  `vendor/` folder that PUC needs at runtime. Don't edit it. PHPCS, ESLint,
  Stylelint and Prettier skip it. PHPStan scans it for types but doesn't analyse
  it.
- `docs/` holds user and developer docs. It doesn't ship in the release zip.

## Conventions

- **Formatting is automatic for edits made with Write or Edit.** A `PostToolUse`
  hook runs phpcbf then phpcs on PHP, eslint on JS, and prettier on
  JS/CSS/JSON/Markdown/YAML. Read the hook output; don't rerun the formatters by
  hand for a file you just edited.
- **Shell edits skip the hook.** Heredocs and `sed -i` don't trigger it, so run
  the formatter yourself after those. Stylelint never runs in the hook; run
  `npm run lint:css` when you touch CSS.
- **PHPCS and ESLint violations reported after an edit are yours to fix.**
- **PHP 7.4 and WordPress 6.0 are the floor.** The plugin header says so,
  Composer resolves dependencies for 7.4, and PHPCompatibility flags anything
  newer. No union types, `match`, named arguments or other 8.0+ syntax.
- **Prefixes:** `r3d_woo_` / `R3D_WOO_` for new functions, constants, options and
  hooks. Two older public filters, `r3d_auto_inject_product_flipbook` and
  `r3d_has_active_subscription`, predate that rule and carry a `phpcs:ignore`.
  Don't rename them; sites hook them.
- **Types live in docblocks, not native declarations.** Most methods are hook
  callbacks, and a native return type turns a filter that returns the wrong type
  into a fatal error. PHPStan reads the `@param` and `@return` tags.
- **Escape on output, sanitize on input.** Every superglobal read needs
  `isset()`, `wp_unslash()` and a `sanitize_*()` call. A nonce check alone
  doesn't make the value safe.

## Tests

- `tests/unit/` uses WP_Mock, with no WordPress or database. The bootstrap
  defines the plugin's constants and requires `includes/main.php`. `R3D_Woo` is a
  singleton, so mock its constructor's WordPress calls in every test (see the
  `instance()` helper in `R3DWooTest.php`).
- `tests/integration/` uses `WP_UnitTestCase` against real WordPress. The
  bootstrap defines `REAL3D_FLIPBOOK_VERSION` to fake Real3D Flipbook, because
  the addon checks only that constant before registering its hooks. It doesn't
  load WooCommerce, so `function_exists( 'WC' )` is false there and the WooCommerce
  hooks never register; call methods directly.
- Run the integration suite inside wp-env. The `--env-cwd` path is the checkout's
  folder name, which may differ from the slug:

    ```bash
    npm run env:start
    npx wp-env run tests-cli --env-cwd=wp-content/plugins/real3d-flipbook-woocommerce-addon-repo composer test:integration
    ```

- CI runs the integration suite across PHP 7.4 to 8.4 and two WordPress
  versions with `tests/bin/install-wp-tests.sh` instead of wp-env, because wp-env
  runs one PHP version at a time. Keep that script.
- PHPStan gets WooCommerce types from `php-stubs/woocommerce-stubs` and
  `r3d_get_flipbook()` from `tests/phpstan-stubs.php`. Add any other Real3D
  Flipbook function you call to that file.

## Releasing

1. Bump the version in the `Version:` header and `R3D_WOO_VERSION` in
   `real3d-flipbook-woocommerce-addon.php`. `composer build` fails if they
   disagree. `tests/integration/WooAddonTest.php` also asserts the version, so
   update it too.
2. Add the release to `CHANGELOG.md`.
3. `composer lint && composer test`, then the integration suite.
4. Merge to `main`, tag `v<version>` and push the tag. `release.yml` checks the
   tag against the `Version:` header, runs `composer build` and attaches the zip
   to a GitHub release. Sites pick it up through plugin-update-checker, which
   installs the first asset on the latest release.

`composer build` on its own gives you the same zip in `dist/` for a manual
install. `dist/` is gitignored.
