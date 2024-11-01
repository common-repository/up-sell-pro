<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use traits\TLavBoostSingleton;

class LavBoostModuleFreeShippingLabel extends LavBoostModule {
	use TLavBoostSingleton;

	public $free_shipping_instance;

	public function run( $args = '' ) {
		$this->createSettingsTab();

		$places = $this->getValue( 'shipping_label_relation_place' )
			? $this->getValue( 'shipping_label_relation_place' )
			: array( 'woocommerce_proceed_to_checkout' );

		if ( $this->getValue( 'shipping_label_enable' ) ) {
			if ( is_array( $places ) && ! empty( $places ) ) {
				foreach ( $places as $place ) {
					add_action( $place, array( $this, 'render' ), 10 );
				}
			}
		}
	}


	public function setFreeShippingInstance( $free_shipping_instance ) {
		$this->free_shipping_instance = $free_shipping_instance;
	}

	public function isOnlyVirtual() {
		$onlyVirtual = false;
		$cart        = WC()->cart;
		if ( $cart ) {
			foreach ( $cart->get_cart() as $cartItem ) {
				$product = $cartItem['data'];

				if ( $product->is_virtual() || $product->is_downloadable() ) {
					$onlyVirtual = true;
				} else {
					$onlyVirtual = false;
					break;
				}

			}
		}

		return $onlyVirtual;
	}

	public function currentShippingMethod() {
		$wc_session = ( isset( WC()->session ) ? WC()->session : null );
		if ( ! $wc_session ) {
			return null;
		}
		$chosen_methods = $wc_session->get( 'chosen_shipping_methods' );
		if ( ! $chosen_methods ) {
			return null;
		}
		$chosen_shipping_id = ( $chosen_methods ? $chosen_methods[0] : '' );

		return $chosen_shipping_id;
	}

	public function beginWith( $string, $start_string ) {
		if ( ! $string ) {
			return null;
		}
		$len = strlen( $start_string );

		return substr( $string, 0, $len ) === $start_string;
	}

	public function getMethodMinAmount( $shipping_id ) {
		$option_name = 'woocommerce_' . str_replace( ':', '_', $shipping_id ) . '_settings';
		$option      = get_option( $option_name );

		return ( isset( $option['method_free_shipping'] ) ? $option['method_free_shipping'] : null );
	}

	function hasDestinationInfo( $package = array() ) {
		$country  = ( ! empty( $package['destination']['country'] ) ? $package['destination']['country'] : null );
		$state    = ( ! empty( $package['destination']['state'] ) ? $package['destination']['state'] : null );
		$postcode = ( ! empty( $package['destination']['postcode'] ) ? $package['destination']['postcode'] : null );
		$city     = ( ! empty( $package['destination']['city'] ) ? $package['destination']['city'] : null );
		// If country is set to AF - this is probably default selection for the first country on the list.
		// Just to be sure, we'll check if city is empty or not.
		if ( $country === 'AF' && ! $city ) {
			$country = null;
		}
		$exists = true;
		// If there's no country, state and postcode, we are probably dealing with "first-timer" or
		// a customer that hasn't filled out checkout form recently.
		if ( ! $country && ! $state && ! $postcode ) {
			$exists = false;
		}

		return $exists;
	}

	public static function shippingZonesList() {
		if ( class_exists( 'WC_Shipping_Zones' ) ) {
			$zones = \WC_Shipping_Zones::get_zones();

			$options = [
				'default' => esc_html__( 'No', 'up-sell-pro' ),
			];
			foreach ( $zones as $key => $zone ) {
				$id   = ( isset( $zone['zone_id'] ) ? $zone['zone_id'] : null );
				$name = ( isset( $zone['zone_name'] ) ? $zone['zone_name'] : null );
				if ( $id && $name ) {
					$options[ $id ] = $name;
				}
			}

			return $options;
		}
	}

	public function getFreeShippingMinAmount() {
		$amount                        = null;
		$only_virtual_products_in_cart = $this->isOnlyVirtual();


		$initial_zone            = ! empty( $this->getValue( 'shipping_label_default_zone' ) ) && $this->getValue( 'shipping_label_default_zone' ) != 'default'
			? $this->getValue( 'shipping_label_default_zone' )
			: 1;
		$enable_custom_threshold = ! empty( $this->getValue( 'shipping_label_enable_custom_threshold' ) )
			? $this->getValue( 'shipping_label_enable_custom_threshold' ) : false;
		$custom_threshold        = ! empty( $this->getValue( 'shipping_label_custom_threshold_value' ) )
			? $this->getValue( 'shipping_label_custom_threshold_value' ) : false;

		if ( $enable_custom_threshold && ! $only_virtual_products_in_cart ) {
			$amount = ( $custom_threshold ?: $amount );

			return apply_filters( 'lav_boost_min_amount', $amount );
		}

		$chosen_shipping_id   = $this->currentShippingMethod();
		$is_flexible_shipping = $this->beginWith( $chosen_shipping_id, 'flexible_shipping' );

		if ( $is_flexible_shipping ) {
			$amount = $this->getMethodMinAmount( $chosen_shipping_id );
			if ( $this->isOnlyVirtual() ) {
				$amount = null;
			}

			return apply_filters( 'lav_boost_min_amount', $amount );
		}

		$amount = null;
		$cart   = WC()->cart;

		if ( $cart ) {
			$packages       = $cart->get_shipping_packages();
			$package        = reset( $packages );
			$zone           = wc_get_shipping_zone( $package );
			$known_customer = $this->hasDestinationInfo( $package );

			if ( ! $known_customer && $initial_zone || $initial_zone == 0 ) {
				$init_zone = \WC_Shipping_Zones::get_zone_by( 'zone_id', $initial_zone );
				// Check if initial zone still exists.
				$zone = ( $init_zone ? $init_zone : $zone );
			}

			foreach ( $zone->get_shipping_methods( true ) as $key => $method ) {

				if ( $method->id === 'free_shipping' ) {
					$instance = ( isset( $method->instance_settings ) ? $method->instance_settings : null );
					$this->setFreeShippingInstance( $instance );
					$min_amount_key = apply_filters( 'lav_boost_free_shipping_instance_key', 'min_amount' );
					$amount         = ( isset( $instance[ $min_amount_key ] ) ? $instance[ $min_amount_key ] : null );
					// If filter fails, go back to default 'min_amount' key.
					if ( ! $amount && isset( $instance['min_amount'] ) ) {
						$amount = $instance['min_amount'];
					}
					break;
				}

				if ( $this->beginWith( $method->id, 'flexible_shipping' ) ) {
					$amount = $this->getMethodMinAmount( $method->id );
				}
			}
			if ( $only_virtual_products_in_cart ) {
				$amount = null;
			}
		}

		return apply_filters( 'lav_boost_min_amount', $amount );
	}

	public function getArgs( $input_string = '', $remaining = null, $amount_for_free_shipping = null ) {
		if ( $remaining ) {
			$input_string = str_replace( '{remaining}', wc_price( $remaining ), $input_string );
		}
		if ( $amount_for_free_shipping ) {
			$input_string = str_replace( '{free_shipping_amount}', wc_price( $amount_for_free_shipping ), $input_string );
		}

		return $input_string;
	}

	public function isFreeShippingCouponApplied() {
		$is_applied      = false;
		$applied_coupons = WC()->cart->get_applied_coupons();
		foreach ( $applied_coupons as $coupon_code ) {
			$coupon = new \WC_Coupon( $coupon_code );
			if ( $coupon->get_free_shipping() ) {
				$is_applied = true;
			}
		}

		return $is_applied;
	}

	public function render( $args = array() ) {
		$amount_for_free_shipping = $this->getFreeShippingMinAmount();
		if ( ! $amount_for_free_shipping ) {
			return;
		}

		if ( is_numeric( $amount_for_free_shipping ) ) {
			$amount_for_free_shipping = (double) $amount_for_free_shipping;
		} else {
			return;
		}

		$show_shipping_before_address = WC()->cart->show_shipping();
		if ( ! $show_shipping_before_address ) {
			return;
		}


		$cart_subtotal       = WC()->cart->get_displayed_subtotal();
		$discount            = WC()->cart->get_discount_total();
		$discount_tax        = WC()->cart->get_discount_tax();
		$price_including_tax = WC()->cart->display_prices_including_tax();
		$price_decimal       = wc_get_price_decimals();
		$is_local_pickup     = $this->beginWith( $this->currentShippingMethod(), 'local_pickup' );
		$percent             = 0;
		$lav_instance        = $this->free_shipping_instance;
		$settings            = [
			'title'             => $this->getValue( 'shipping_label_add_title' ),
			'description'       => $this->getValue( 'shipping_label_add_description' ),
			'qualified_message' => $this->getValue( 'shipping_label_qualified_message' ),
		];

		$lav_requires         = ( isset( $lav_instance['requires'] ) ? $lav_instance['requires'] : '' );
		$lav_ignore_discounts = ( isset( $lav_instance['ignore_discounts'] ) ? $lav_instance['ignore_discounts'] : '' );
		// Are coupon discounts ignored?

		if ( $lav_ignore_discounts === 'yes' ) {
			$discount     = 0;
			$discount_tax = 0;
		}

		if ( $is_local_pickup && $this->getValue('shipping_label_local_pickup_enable')) {
			return;
		}

		if ( $price_including_tax ) {
			$cart_subtotal = round( $cart_subtotal - ( $discount + $discount_tax ), $price_decimal );
		} else {
			$cart_subtotal = round( $cart_subtotal - $discount, $price_decimal );
		}

		$cart_reached_threshold = $cart_subtotal >= $amount_for_free_shipping;

		$free_shipping_pass = false;

		if ( $cart_reached_threshold ) {
			$free_shipping_pass = true;
			$percent            = 100;
			$remaining          = '0,00';
		} else {
			$remaining = $amount_for_free_shipping - $cart_subtotal;
			if ( $amount_for_free_shipping != 0 ) {
				$percent = 100 - $remaining / $amount_for_free_shipping * 100;
			}
		}

		if ( WC()->cart->get_shipping_total() == 0 && ! $is_local_pickup ) {
			$free_shipping_pass = true;
		}


		if ( $this->isFreeShippingCouponApplied() ) {
			$free_shipping_pass = true;
			if ( $lav_requires === 'coupon' ) {
				$free_shipping_pass = true;
			}
			if ( $lav_requires === 'both' && $cart_reached_threshold ) {
				$free_shipping_pass = true;
			}
		} else {
			if ( $lav_requires === 'coupon' ) {
				return;
			}
			if ( $lav_requires === 'both' ) {
				$free_shipping_pass = false;
				if ( $cart_reached_threshold ) {
					$percent                 = 100;
					$settings['description'] = esc_html__( 'Waiting for Free Shipping coupon', 'up-sell-pro' );
				}

			}
		}

		$title             = $this->getArgs( $settings['title'], $remaining, $amount_for_free_shipping );
		$description       = $this->getArgs( $settings['description'], $remaining, $amount_for_free_shipping );
		$qualified_message = $this->getArgs( $settings['qualified_message'], $remaining, $amount_for_free_shipping );
		$output            = '';
		$outputTemplate    = '<div class="lav-boost lav-boost-shipping-label">
                                <h2 class="shipping-label-title">' . wp_kses_post( $title ) . '</h2>
                                <div class="progress-bar-wrapper">
	                                <div class="progress-bar">
	                                    <div class="progress" style="width: ' . esc_attr( $percent ) . '%;"></div>
	                                </div>
	                                <div class="shipping-label-description">' . wp_kses_post( $description ) . '</div>
                                </div>
                              </div>';
		if ( $free_shipping_pass ) {
			if ( ! empty( $this->getValue( 'shipping_label_qualified_message_enable' ) ) && ! empty( $this->getValue( 'shipping_label_qualified_message' ) ) ) {
				$output .= '<div class="lav-boost lav-boost-shipping-label"><h4 class="shipping-label-description active">' . esc_html( $qualified_message ) . '</h4></div>';
			} else {
				$output .= '';
			}
		} else {
			$output .= $outputTemplate;
		}
		echo apply_filters( 'lav_boost_progress_bar_html', $output );
	}


	public function getFields(): array {
		return array(
			'name'   => 'shipping_label',
			'title'  => esc_html__( 'Shipping Label', 'up-sell-pro' ),
			'icon'   => 'fas fa-shopping-cart',
			'fields' => array(
				array(
					'id'       => 'shipping_label_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable Shipping Label', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable Free Shipping Label', 'up-sell-pro' ),
					'default'  => '1',
				),

				array(
					'id'         => 'shipping_label_relation_place',
					'type'       => 'button_set',
					'multiple'   => true,
					'title'      => esc_html__( 'Shipping Label place', 'up-sell-pro' ),
					'options'    => array(
						'woocommerce_review_order_before_submit' => esc_html__( 'Checkout', 'up-sell-pro' ),
						'woocommerce_proceed_to_checkout'        => esc_html__( 'Cart', 'up-sell-pro' ),
					),
					'default'    => array( 'woocommerce_proceed_to_checkout' ),
					'dependency' => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
					'subtitle'   => esc_html__( 'Place to put Free Shipping Label', 'up-sell-pro' ),
				),

				array(
					'id'         => 'shipping_label_default_zone',
					'type'       => 'select',
					'title'      => esc_html__( 'Default shipping zone', 'up-sell-pro' ),
					'settings'   => array( 'width' => '50%' ),
					'options'    => 'LavBoostModuleFreeShippingLabel::shippingZonesList',
					'subtitle'   => esc_html__( 'This zone\'s free shipping threshold will be used only if customer didn\'t already enter address.', 'up-sell-pro' ),
					'default'    => 'default',
					'chosen'     => false,
					'dependency' => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'shipping_label_enable_custom_threshold',
					'type'       => 'switcher',
					'title'      => esc_html__( 'Enable Custom threshold', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'This will ignore free shipping threshold in WooCommerce settings', 'up-sell-pro' ),
					'default'    => '0',
					'dependency' => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'shipping_label_custom_threshold_value',
					'type'       => 'number',
					'title'      => esc_html__( 'Custom threshold', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'This will be used only to determine if we should show the bar / label', 'up-sell-pro' ),
					'default'    => 50,
					'dependency' => array(
						'shipping_label_enable|shipping_label_enable_custom_threshold',
						'==|==',
						'1|1'
					),
				),

				array(
					'id'          => 'shipping_label_add_title',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Free delivery on orders over {free_shipping_amount}', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'shipping_label_add_title_placeholder',
					'type'       => 'text',
					'title'      => esc_html__( 'Amount placeholder', 'up-sell-pro' ),
					'default'    => esc_html__( '{free_shipping_amount}', 'up-sell-pro' ),
					'attributes' => array(
						'readonly' => 'readonly'
					),
					'dependency' => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'shipping_label_add_description',
					'type'        => 'text',
					'title'       => esc_html__( 'Section description', 'up-sell-pro' ),
					'default'     => esc_html__( 'Add at least {remaining} more to get free shipping!', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'shipping_label_add_description_placeholder',
					'type'       => 'text',
					'title'      => esc_html__( 'Remaining placeholder', 'up-sell-pro' ),
					'default'    => esc_html__( '{remaining}', 'up-sell-pro' ),
					'attributes' => array(
						'readonly' => 'readonly'
					),
					'dependency' => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),
				array(
					'id'         => 'shipping_label_qualified_message_enable',
					'type'       => 'switcher',
					'title'      => esc_html__( 'Enable Free shipping message', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Enable\Disable Qualified for free shipping message', 'up-sell-pro' ),
					'default'    => '1',
					'dependency' => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'shipping_label_qualified_message',
					'type'        => 'text',
					'title'       => esc_html__( 'Free shipping message', 'up-sell-pro' ),
					'default'     => esc_html__( 'You have free shipping!', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array(
						'shipping_label_enable|shipping_label_qualified_message_enable',
						'==|==',
						'1|1'
					),
				),

				array(
					'id'         => 'shipping_label_local_pickup_enable',
					'type'       => 'switcher',
					'title'      => esc_html__( 'Disable if Local pickup is chosen', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Enable\Disable if Local pickup is chosen', 'up-sell-pro' ),
					'default'    => '1',
					'dependency' => array( 'shipping_label_enable', '==', '1', '', 'visible' ),
				),
			)
		);
	}


}
