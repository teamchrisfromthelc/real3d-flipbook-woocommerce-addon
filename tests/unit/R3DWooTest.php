<?php
/**
 * Unit tests for R3D_Woo.
 *
 * @package R3DWoo
 */

declare( strict_types=1 );

namespace R3DWoo\Tests\Unit;

use R3D_Woo;
use stdClass;
use WP_Mock;
use WP_Mock\Tools\TestCase as WPMockTestCase;

/**
 * Logic in R3D_Woo that does not need a database or WooCommerce.
 */
class R3DWooTest extends WPMockTestCase {

	/**
	 * Returns the singleton, mocking what its constructor calls on first use.
	 *
	 * @return R3D_Woo
	 */
	private function instance(): R3D_Woo {
		WP_Mock::userFunction( 'plugin_dir_path' )->andReturn( '/plugin/' );
		WP_Mock::userFunction( 'plugin_dir_url' )->andReturn( 'https://example.test/plugin/' );
		WP_Mock::userFunction( 'register_activation_hook' );
		WP_Mock::userFunction( 'register_deactivation_hook' );

		return R3D_Woo::get_instance();
	}

	public function test_plugins_loaded_stops_with_notice_when_real3d_flipbook_is_missing() {
		$instance = $this->instance();

		WP_Mock::userFunction( 'plugin_basename' )->andReturn( 'real3d-flipbook-woocommerce-addon' );
		WP_Mock::userFunction( 'load_plugin_textdomain' )->once();
		WP_Mock::expectActionAdded( 'admin_notices', array( $instance, 'admin_notice_missing_real3d_flipbook' ) );
		WP_Mock::expectActionNotAdded( 'save_post', array( $instance, 'save_meta_box' ) );

		$instance->plugins_loaded();

		$this->assertConditionsMet();
	}

	public function test_filter_purchased_or_subscription_keeps_access_already_granted() {
		WP_Mock::userFunction( 'get_the_ID' )->never();

		$this->assertTrue( $this->instance()->filter_purchased_or_subscription( true ) );
	}

	public function test_filter_purchased_or_subscription_denies_outside_a_product() {
		WP_Mock::userFunction( 'get_the_ID' )->once()->andReturn( false );

		$this->assertFalse( $this->instance()->filter_purchased_or_subscription( false ) );
	}

	/**
	 * WooCommerce passes false when wc_get_order() cannot load the order.
	 *
	 * @dataProvider not_an_order
	 *
	 * @param mixed $order Value passed in place of a WC_Order.
	 */
	public function test_change_thankyou_sub_title_ignores_anything_but_an_order( $order ) {
		WP_Mock::userFunction( 'get_option' )->never();

		$this->assertSame( 'Thank you.', $this->instance()->change_thankyou_sub_title( 'Thank you.', $order ) );
	}

	/**
	 * Values WooCommerce or a third party can hand the filter.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function not_an_order(): array {
		return array(
			'false'      => array( false ),
			'null'       => array( null ),
			'order ID'   => array( 123 ),
			'stdClass'   => array( new stdClass() ),
		);
	}
}
