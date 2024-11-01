<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleCheckoutPromo extends LavBoostModule {
	use TLavBoostSingleton;

	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'checkout_promo_enable' ) ) {

			add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 99 );

			add_action( 'woocommerce_review_order_before_submit', array( $this, 'render' ), 9999 );
			add_action('woocommerce_before_calculate_totals', array( $this, 'applyDiscountToCart' ), 5);

			// AJAX action to add a product to cart
			add_action('wp_ajax_promo_add_to_cart', array( $this, 'addToCartAjax' ));
			add_action('wp_ajax_nopriv_promo_add_to_cart', array( $this, 'addToCartAjax' ));

			// AJAX action to remove a product from cart
			add_action('wp_ajax_promo_remove_cart_item', array( $this, 'removeCartItemAjax' ));
			add_action('wp_ajax_nopriv_promo_remove_cart_item', array( $this, 'removeCartItemAjax' ));
		}
	}

	public function localize() {
		wp_localize_script( 'lav-boost', 'lavBoostPromo', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nonce-lav-boost-promo' )
		) );
	}

	public function addToCartAjax() {
		if (isset($_POST['product_id'])) {
			if ( empty( $_POST['nonce'] ) ) {
				wp_die( '0' );
			}

			if ( empty( $_POST['product_id']) || empty($_POST['discount']) || empty($_POST['discount_type']) ) {
				wp_die( '0' );
			}

			if ( check_ajax_referer( 'nonce-lav-boost-promo', 'nonce', false ) ) {
				$product_id = absint(wc_clean($_POST['product_id']));
				$discount = wc_clean($_POST['discount']);
				$type = wc_clean($_POST['discount_type']);
				$product_price = floatval(get_post_meta($product_id, '_regular_price', true));
				$discount_amount = $this->getDiscountValue($product_price, $type, $discount);
				$cart_item_data = array(
					'lav_boost_discount_key' => 'lav_boost_discount_meta_key',
					'lav_boost_promo_is_in_cart' => 'lav_boost_promo_is_in_cart',
					'lav_boost_discount_price' => $discount_amount
				);

				$keyItem = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

				WC()->cart->calculate_totals();
			    wp_send_json( [
					'title'    => esc_html__( 'Product has been added to your cart', 'up-sell-pro' ),
					'data' => $cart_item_data,
                    'key' => $keyItem
				] );
				wp_die();
			} else {
				wp_die( esc_html__( 'Access denied', 'up-sell-pro' ), esc_html__( 'Denied', 'up-sell-pro' ), 403 );
			}

		}

		wp_die('0');
	}

	function removeCartItemAjax() {
		if (isset($_POST['cart_item_key'])) {
			if ( empty( $_POST['nonce'] ) ) {
				wp_die( '0' );
			}

			if ( check_ajax_referer( 'nonce-lav-boost-promo', 'nonce', false ) ) {
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					if (isset($cart_item['lav_boost_promo_is_in_cart'])) {
						WC()->cart->remove_cart_item( $cart_item_key );
						wp_send_json( [
							'title'    => esc_html__( 'Product has been removed from your cart', 'up-sell-pro' ),
						] );
						break;
					}
				}
				wp_die();
            }else{
				wp_die( esc_html__( 'Access denied', 'up-sell-pro' ), esc_html__( 'Denied', 'up-sell-pro' ), 403 );
            }
		}
		wp_die('0');
	}

	public function applyDiscountToCart($cart) {
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}

		foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
			if (isset($cart_item['lav_boost_discount_key'])) {
				$cart_item['data']->set_price(floatval($cart_item['data']->get_price()) - $cart_item['lav_boost_discount_price']);
				$product_name_new = sprintf( esc_html__( '(Deal) %s', 'up-sell-pro' ),  $cart_item['data']->get_name() );
				$cart_item['data']->set_name($product_name_new);
			}
		}

	}

	public function getDiscountValue( $price, $discountType, $discountValue, $quantity = 1 ) {

		if ( empty( $price ) || empty( $discountType ) ) {
			return 0;
		}

		if ( empty( $discountValue ) || empty( $discountType ) ) {
			return 0;
		}

		if ( $discountType != 'percent' ) {
			if ( $discountValue >= ( $price * $quantity ) ) {
				return $price * $quantity;
			} else {
				return $discountValue;
			}
		}

		if ( $discountValue >= 100 ) {
			return $price * $quantity;
		}

		if ( $discountValue > 0 ) {
			return ( ( floatval($price) ) / 100 * $discountValue ) * $quantity;
		}
	}

	public function getPriceWithDiscount( $price, $discountType, $discountValue) {
	    $amount = $this->getDiscountValue($price, $discountType, $discountValue);
	    return floatval($price) - $amount;
	}

	public function isPromo( $args, $orderProductsIds ) {
		if ( empty( $args['checkout_promo_item_condition'] ) ) {
			return true;
		}
		if ( ! empty( $args['checkout_promo_item_condition'] ) && $args['checkout_promo_item_condition'] === 'some' ) {
			return $this->hasSome( $orderProductsIds, $args['checkout_promo_products_items'] );
		}
		if ( ! empty( $args['checkout_promo_item_condition'] ) && $args['checkout_promo_item_condition'] === 'every' ) {
			return $this->hasEvery( $orderProductsIds, $args['checkout_promo_products_items'] );
		}
		if ( ! empty( $args['checkout_promo_item_condition'] ) && $args['checkout_promo_item_condition'] === 'any' ) {
			return ! $this->hasSome( $orderProductsIds, $args['checkout_promo_products_items'] );
		}

		return false;
	}

	public function hasSome( $orderProductsIds, $infoItemsIds ) {
		$intersection = array_intersect( $orderProductsIds, $infoItemsIds );

		if ( ! empty( $intersection ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function hasEvery( $orderProductsIds, $infoItemsIds ) {
		$diff = array_diff( $infoItemsIds, $orderProductsIds );

		if ( empty( $diff ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function renderPromo( $values ) {

		if ( empty( $values['checkout_promo_product'] ) ) {
			return;
		}
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $values['checkout_promo_product'] );
			$ability = ( $product && $product->is_in_stock() && $product->get_price() > 0 );
			$id = $values['checkout_promo_product'];
			$discount = !empty($values['checkout_discount_value']) ? $values['checkout_discount_value'] : 0;
			$type = !empty($values['checkout_discount_type']) ? $values['checkout_discount_type'] : 'percent';
			$isInCart = '';
			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				if (isset($cart_item['lav_boost_promo_is_in_cart']) && $cart_item["product_id"] == $values['checkout_promo_product']) {
					$isInCart = 'checked';
					break;
				}
			}
			?>

			<?php if ( ! empty( $product ) && $ability ): ?>
                <div class="lav-boost lav-boost-checkout-promo">
                    <div class="product-card">
						<?php if ( ! empty( $values['checkout_promo_item_badge'] ) ): ?>
                            <div class="badge">
								<?php echo esc_html( $values['checkout_promo_item_badge'] ); ?>
                            </div>
						<?php endif; ?>
                        <div class="product-tumb">
                            <a href="<?php echo esc_url( get_permalink( $id ) ); ?>">
								<?php echo $product->get_image( 'full' ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </a>
                        </div>
                        <div class="product-details">
                            <a class="up-sell-card-title" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
                                <h3 class="up-sell-card-title">
									<?php echo wp_kses_post( $product->get_name() ); ?>
                                </h3>
                            </a>
	                        <?php if ( ! empty( $values['checkout_promo_item_title'] ) || ! empty( $values['checkout_promo_item_note'] )): ?>
                                <div class="promo-body">
			                        <?php if ( ! empty( $values['checkout_promo_item_title'] ) ): ?>
                                        <h6 class="donation-title">
					                        <?php echo esc_html( $values['checkout_promo_item_title'] ); ?>
                                        </h6>
			                        <?php endif; ?>
			                        <?php if ( ! empty( $values['checkout_promo_item_note'] ) ): ?>
                                        <p class="checkout-promo-description">
					                        <?php echo esc_html( $values['checkout_promo_item_note'] ); ?>
                                        </p>
			                        <?php endif; ?>
                                </div>
	                        <?php endif; ?>
                            <div class="product-bottom-details">
                                <div class="product-price">
                                    <span class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'card-price' ) ); ?>">
                                        <del aria-hidden="true">
                                            <bdi>
                                               <?php echo sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(),  number_format(floatval($product->get_price()), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator())  ); ?>
                                            </bdi>
                                        </del>
                                        <ins>
                                           <?php echo sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), number_format($this->getPriceWithDiscount($product->get_price(), $type, $discount ), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() )); ?>
                                        </ins>
                                    </span>
                                </div>
                                <div class="product-links">
                                <?php if ( ! empty( $id ) && ! empty( $ability ) ): ?>
                                    <input <?php echo esc_attr( $isInCart ); ?>
                                            type="checkbox"
                                            data-product-id="<?php echo esc_attr( $id ); ?>"
                                            data-discount="<?php echo esc_attr( $discount ); ?>"
                                            data-discount-type="<?php echo esc_attr( $type ); ?>"
                                            class="input-checkbox" name="promo-checkbox"
                                            id="promo-checkbox" />
                                    <label for="promo-checkbox">
		                                <?php echo esc_html( $values['checkout_promo_item_label'] ); ?>
                                    </label>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			<?php endif; ?>
			<?php
		}


	}

	public function render( $arguments = '' ) {
		$cart            = WC()->cart;
		$cartProductsIds = [];

		if ( ! is_wp_error( $cart ) ) {
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				array_push( $cartProductsIds, $cart_item['product_id'] );
			}
		}

		if ( ! empty( $this->getValue( 'checkout_promo_items' ) ) && is_array( $this->getValue( 'checkout_promo_items' ) ) ) {
			foreach ( $this->getValue( 'checkout_promo_items' ) as $value ) {
				if ( is_array( $value ) && ! empty( $value['checkout_promo_products_items'] ) ) {
					if ( $this->isPromo( $value, $cartProductsIds ) ) {
						$this->renderPromo( $value );
						break;
					}
				}
			}
		}
	}


	public function getFields(): array {
		return array(
			'name'   => 'checkout_promo_products',
			'title'  => esc_html__( 'Checkout Promo', 'up-sell-pro' ),
			'icon'   => 'fas fa-store',
			'fields' => array(
				array(
					'id'       => 'checkout_promo_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable checkout promo', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable checkout promo on the checkout page', 'up-sell-pro' ),
					'default'  => '0',
				),

				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'You can add only one promo item if you need more promo items so please buy paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array( 'checkout_promo_enable', '==', '1' ),
				),

				array(
					'id'           => 'checkout_promo_items',
					'type'         => 'repeater',
					'max'          => 1,
					'title'        => esc_html__( 'Conditions', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Conditions to show promo offer', 'up-sell-pro' ),
					'button_title' => esc_html__( 'Add Notice', 'up-sell-pro' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'checkout_promo_products_items',
							'type'        => 'select',
							'title'       => esc_html__( 'Products in order', 'up-sell-pro' ),
							'subtitle'    => esc_html__( 'Choose products', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'placeholder' => esc_html__( 'Select product', 'up-sell-pro' ),
							'options'     => 'posts',
							'query_args'  => array(
								'post_type'      => 'product',
								'status'         => 'publish',
								'posts_per_page' => - 1,
								'tax_query'      => array(
									array(
										'taxonomy' => 'product_type',
										'field'    => 'name',
										'terms'    => array( 'simple' ),
									),
									array(
										'taxonomy' => 'product_visibility',
										'field'    => 'name',
										'terms'    => 'outofstock',
										'operator' => 'NOT IN',
									),
									array(
										'taxonomy' => 'product_visibility',
										'terms'    => array( 'exclude-from-catalog' ),
										'field'    => 'name',
										'operator' => 'NOT IN',
									),
								),
							),
						),
						array(
							'id'          => 'checkout_promo_item_condition',
							'type'        => 'select',
							'title'       => esc_html__( 'Condition', 'up-sell-pro' ),
							'chosen'      => true,
							'placeholder' => esc_html__( 'Select condition', 'up-sell-pro' ),
							'options'     => array(
								'some'  => esc_html__( 'Some', 'up-sell-pro' ),
								'every' => esc_html__( 'Every', 'up-sell-pro' ),
								'any'   => esc_html__( 'Any', 'up-sell-pro' ),
							),
							'default'     => array( 'some' )
						),

						array(
							'id'          => 'checkout_promo_item_title',
							'type'        => 'text',
							'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
							'default'     => esc_html__( 'Put title text here', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),

						array(
							'id'          => 'checkout_promo_item_badge',
							'type'        => 'text',
							'title'       => esc_html__( 'Badge', 'up-sell-pro' ),
							'subtitle'    => esc_html__( 'Badge to show', 'up-sell-pro' ),
							'default'     => esc_html__( 'Hot', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),

						array(
							'id'          => 'checkout_promo_item_label',
							'type'        => 'text',
							'title'       => esc_html__( 'Label', 'up-sell-pro' ),
							'default'     => esc_html__( 'Add Promo Product', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),

						array(
							'id'          => 'checkout_promo_item_note',
							'type'        => 'textarea',
							'title'       => esc_html__( 'Message', 'up-sell-pro' ),
							'default'     => esc_html__( 'One time offer! Get this product with HUGE discount right now! Click the checkbox above to add this product to your order. Get it now, because you won\'t have this chance again.', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),

						array(
							'id'          => 'checkout_promo_product',
							'type'        => 'select',
							'title'       => esc_html__( 'Promo product', 'up-sell-pro' ),
							'chosen'      => true,
							'settings'    => array( 'width' => '50%' ),
							'ajax'        => true,
							'multiple'    => false,
							'placeholder' => esc_html__( 'Select product', 'up-sell-pro' ),
							'options'     => 'posts',
							'query_args'  => array(
								'post_type'      => 'product',
								'status'         => 'publish',
								'posts_per_page' => - 1,
								'tax_query'      => array(
									array(
										'taxonomy' => 'product_type',
										'field'    => 'name',
										'terms'    => array( 'simple' ),
									),
									array(
										'taxonomy' => 'product_visibility',
										'field'    => 'name',
										'terms'    => 'outofstock',
										'operator' => 'NOT IN',
									),
									array(
										'taxonomy' => 'product_visibility',
										'terms'    => array( 'exclude-from-catalog' ),
										'field'    => 'name',
										'operator' => 'NOT IN',
									),
								),
							),
						),

						array(
							'id'      => 'checkout_discount_type',
							'type'    => 'button_set',
							'title'   => esc_html__( 'Discount type', 'up-sell-pro' ),
							'options' => array(
								'fixed'   => esc_html__( 'Fixed', 'up-sell-pro' ),
								'percent' => esc_html__( 'Percent', 'up-sell-pro' ),
							),
							'default' => 'percent',
						),

						array(
							'id'      => 'checkout_discount_value',
							'type'    => 'number',
							'title'   => esc_html__( 'Discount value', 'up-sell-pro' ),
							'default' => 10,
							'step'    => 0.1,
							'min'     => 0.01,
						),
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
					),
					'dependency'   => array( 'checkout_promo_enable', '==', '1', '', 'visible' ),
				),
			)
		);
	}
}
