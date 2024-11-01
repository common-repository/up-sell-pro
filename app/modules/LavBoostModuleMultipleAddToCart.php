<?php

namespace modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}
use traits\TLavBoostSingleton;
use WC_Form_Handler;



class LavBoostModuleMultipleAddToCart {

	use TLavBoostSingleton;

	public function init() {
		add_action( 'wp_loaded', array( $this, 'multipleProductsToCart' ), 11 );
	}

	public static function multipleProductsToCart() {
		if ( empty( $_REQUEST['add-to-cart'] ) || false === strpos( $_REQUEST['add-to-cart'], ',' ) ) {
			return null;
		}
		$ids = explode( ',', sanitize_text_field( $_REQUEST['add-to-cart'] ) );
		$product_ids        = array_map( 'absint', array_filter( $ids ) );
		$redirect_after_add = get_option( 'woocommerce_cart_redirect_after_add' );
		$message            = '';

		$added_all_to_cart          = true;
		$products_not_added_to_cart = array();

		foreach ( $product_ids as $product_add_to_cart ) {
			$product     = wc_get_product( $product_add_to_cart );
			$quantity    = 1;
			$add_to_cart = false;


			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_add_to_cart, $quantity, 0);

			if ( ! $passed_validation ) {
				continue;
			}

			$add_to_cart = WC()->cart->add_to_cart( $product_add_to_cart, $quantity, 0, array() );


			if ( false === $add_to_cart ) {
				$added_all_to_cart            = false;
				$products_not_added_to_cart[] = $product->get_title();
			}
		}

		if ( $added_all_to_cart ) {
			$message = esc_html__( 'All products were added to your cart.', 'up-sell-pro' );
			$message = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', wc_get_cart_url(), __( 'View cart', 'up-sell-pro' ), $message );
		}

		if ( ! empty( $products_not_added_to_cart ) ) {

			$product_title = $products_not_added_to_cart[0];

			// Translators: Product title.
			$message = sprintf( esc_html__( 'Sorry, the following product could not be added to the cart: "%s"', 'up-sell-pro' ), $product_title );

			if ( count( $products_not_added_to_cart ) > 1 ) {
				$product_title = implode( '", "', $products_not_added_to_cart );

				// Translators: Product title.
				$message = sprintf( esc_html__( 'Sorry, the following products could not be added to the cart: "%s"', 'up-sell-pro' ), $product_title );
			}
		}

		$message_type = $added_all_to_cart ? 'success' : 'error';
		wc_add_notice( $message, $message_type );

		$current_url = esc_url_raw( add_query_arg( null, null ) );
		$current_url = remove_query_arg( 'add-to-cart', $current_url );
		wp_redirect( $current_url );
		exit;
	}

}
