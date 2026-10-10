<?php
/**
 * Bootstrap for unit tests.
 *
 * No WordPress, no database. WP functions are mocked with WP_Mock, so these run
 * anywhere in milliseconds. Anything needing real WP behaviour (the database,
 * the query loop, core hooks actually firing) belongs in tests/integration/.
 *
 * @package R3DWoo
 */

declare( strict_types=1 );

$r3d_woo_autoload = __DIR__ . '/../vendor/autoload.php';

if ( ! file_exists( $r3d_woo_autoload ) ) {
	fwrite( STDERR, "Run 'composer install' before running the tests.\n" );
	exit( 1 );
}

// WP_Mock relies on Patchwork to redefine functions, which must be loaded
// before the code under test is autoloaded.
require_once $r3d_woo_autoload;

// Constants the code under test expects at include time. Unit tests never load
// WordPress or the entry point, so anything the entry point defines has to be
// stubbed here. Add project-specific ones alongside these.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Defined by real3d-flipbook-woocommerce-addon.php, which unit tests skip.
if ( ! defined( 'R3D_WOO_VERSION' ) ) {
	define( 'R3D_WOO_VERSION', '0.0.0-test' );
}

if ( ! defined( 'R3D_WOO_FILE' ) ) {
	define( 'R3D_WOO_FILE', dirname( __DIR__ ) . '/real3d-flipbook-woocommerce-addon.php' );
}

WP_Mock::bootstrap();

// Not PSR-4 autoloadable: a global class in a WordPress-style file.
require_once dirname( __DIR__ ) . '/includes/main.php';
