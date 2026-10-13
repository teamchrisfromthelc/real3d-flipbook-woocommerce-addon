<?php
/**
 * Bootstrap for integration tests.
 *
 * Boots real WordPress from the core test library, then loads the code under
 * test before WP finishes initializing. How that happens differs by kind, and
 * setup.sh splices in the right one: a plugin is required by its entry point,
 * a theme is switched to. Both have to run before WordPress reads the active
 * plugin and theme options, which is why they hook muplugins_loaded.
 *
 * WP_TESTS_DIR is set automatically inside wp-env, so these run there rather
 * than on the host — the test library and the database both live in the
 * container. On the host, point WP_TESTS_DIR at a separate WordPress develop
 * checkout: export WP_TESTS_DIR=/path/to/wordpress-develop/tests/phpunit
 *
 * The --env-cwd path below uses the project's directory name, which wp-env
 * mounts as-is; it is not necessarily the slug.
 *
 * @package R3DWoo
 */

declare( strict_types=1 );

$r3d_woo_autoload = __DIR__ . '/../vendor/autoload.php';

if ( file_exists( $r3d_woo_autoload ) ) {
	require_once $r3d_woo_autoload;
}

$r3d_woo_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $r3d_woo_tests_dir ) {
	$r3d_woo_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

$r3d_woo_functions = $r3d_woo_tests_dir . '/includes/functions.php';

if ( ! file_exists( $r3d_woo_functions ) ) {
	fwrite(
		STDERR,
		"Could not find the WordPress test library at {$r3d_woo_tests_dir}.\n" .
		"Integration tests run inside wp-env, not on the host:\n" .
		"  npx wp-env run tests-cli \\\n" .
		"    --env-cwd=wp-content/{plugins|themes}/<project-folder> \\\n" .
		"    composer test:integration\n" .
		"Or set WP_TESTS_DIR to a wordpress-develop/tests/phpunit checkout.\n"
	);
	exit( 1 );
}

require_once $r3d_woo_functions;

/**
 * Load this plugin before WordPress finishes booting.
 *
 * Plugins under test are not in the active-plugins option, so nothing else
 * loads them. muplugins_loaded fires before WordPress reads that option.
 *
 * @return void
 */
function r3d_woo_manually_load_plugin() {
	// Real3D Flipbook is a commercial plugin the test site does not have. The
	// addon only checks this constant before registering its hooks.
	if ( ! defined( 'REAL3D_FLIPBOOK_VERSION' ) ) {
		define( 'REAL3D_FLIPBOOK_VERSION', '4.23' );
	}

	require dirname( __DIR__ ) . '/real3d-flipbook-woocommerce-addon.php';
}
tests_add_filter( 'muplugins_loaded', 'r3d_woo_manually_load_plugin' );

require $r3d_woo_tests_dir . '/includes/bootstrap.php';
