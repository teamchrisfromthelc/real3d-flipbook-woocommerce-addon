<?php

class ThankYouPageTest extends WP_UnitTestCase {

	public function test_has_active_subscription_returns_false_when_not_logged_in() {
		wp_set_current_user( 0 );
		$instance = R3D_Woo::get_instance();
		$this->assertFalse( $instance->has_active_subscription() );
	}

	public function test_check_user_bought_returns_false_when_not_logged_in() {
		wp_set_current_user( 0 );
		$instance = R3D_Woo::get_instance();
		$this->assertFalse( $instance->check_user_bought_variation_with_flipbook() );
	}

	public function test_filter_purchased_or_subscription_returns_true_when_already_granted() {
		$instance = R3D_Woo::get_instance();
		$this->assertTrue( $instance->filter_purchased_or_subscription( true ) );
	}

	/**
	 * WooCommerce passes false into woocommerce_thankyou_order_received_text when
	 * wc_get_order() cannot load the order. Calling get_items() on it was fatal.
	 */
	public function test_change_thankyou_sub_title_returns_title_unchanged_for_false_order() {
		$instance = R3D_Woo::get_instance();
		$this->assertSame(
			'Thank you.',
			$instance->change_thankyou_sub_title( 'Thank you.', false )
		);
	}

	public function test_change_thankyou_sub_title_returns_title_unchanged_for_null_order() {
		$instance = R3D_Woo::get_instance();
		$this->assertSame(
			'Thank you.',
			$instance->change_thankyou_sub_title( 'Thank you.', null )
		);
	}

	public function test_change_thankyou_sub_title_returns_title_unchanged_when_order_arg_omitted() {
		$instance = R3D_Woo::get_instance();
		$this->assertSame(
			'Thank you.',
			$instance->change_thankyou_sub_title( 'Thank you.' )
		);
	}

	public function test_change_thankyou_sub_title_returns_title_unchanged_for_non_order_object() {
		$instance = R3D_Woo::get_instance();
		$this->assertSame(
			'Thank you.',
			$instance->change_thankyou_sub_title( 'Thank you.', new stdClass() )
		);
	}
}
