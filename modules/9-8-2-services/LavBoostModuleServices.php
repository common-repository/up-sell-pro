<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleServices extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'services_enable' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 99 );
			add_action( 'woocommerce_after_add_to_cart_quantity', array( $this, 'render' ), 9999 );

			if ( $this->getValue( 'services_name_change' ) ) {
				add_action( 'woocommerce_before_calculate_totals', array( $this, 'changeServiceName' ), 5 );
			}
			// AJAX action to add a product to cart
			add_action( 'wp_ajax_service_add_to_cart', array( $this, 'addToCartAjax' ) );
			add_action( 'wp_ajax_nopriv_service_add_to_cart', array( $this, 'addToCartAjax' ) );

			// AJAX action to remove a product from cart
			add_action( 'wp_ajax_service_remove_cart_item', array( $this, 'removeCartItemAjax' ) );
			add_action( 'wp_ajax_nopriv_service_remove_cart_item', array( $this, 'removeCartItemAjax' ) );

			// remove a product if main was removed
			add_action( 'woocommerce_remove_cart_item', array( $this, 'removeItemAction' ), 20, 2 );
			add_action( 'woocommerce_cart_item_removed', array( $this, 'removeItemAction' ), 10, 2 );
		}
	}

	public function localize() {
		wp_localize_script( 'lav-boost', 'lavBoostServices', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nonce-lav-boost-services' )
		) );
	}

	public function removeItemAction( $cart_item_key, $cart ) {
		$product_id = ! empty( $cart->removed_cart_contents[ $cart_item_key ]['product_id'] )
			? $cart->removed_cart_contents[ $cart_item_key ]['product_id']
			: null;
		if ( $product_id ) {
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				$removedMeta = ! empty( $cart_item[ 'lav_boost_services_main_product-' . $product_id ] )
					? $cart_item[ 'lav_boost_services_main_product-' . $product_id ]
					: null;
				if ( 'lav_boost_services_main_product-' . $product_id == $removedMeta ) {
					$cart->remove_cart_item( $cart_item_key );
				}
			}
		}
	}

	public function changeServiceName( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['lav_boost_services_new_name'] ) ) {
				$cart_item['data']->set_name( $cart_item['lav_boost_services_new_name'] );
			}
		}

	}

	public function addToCartAjax() {
		if ( isset( $_POST['product_id'] ) && isset( $_POST['current_id'] ) ) {
			if ( empty( $_POST['nonce'] ) ) {
				wp_die( '0' );
			}

			if ( check_ajax_referer( 'nonce-lav-boost-services', 'nonce', false ) ) {
				$product_id = absint( wc_clean( $_POST['product_id'] ) );
				$current_id = absint( wc_clean( $_POST['current_id'] ) );

				$currentProduct     = wc_get_product( $current_id );
				$currentProductName = $currentProduct->get_name();

				$serviceProduct     = wc_get_product( $product_id );
				$serviceProductName = $serviceProduct->get_name();


				$cart_item_data = array(
					'lav_boost_services_key-' . $current_id                            => 'lav_boost_services_meta_key-' . $current_id,
					'lav_boost_services_is_in_cart-' . $current_id . '-' . $product_id => 'lav_boost_services_is_in_cart-' . $current_id . '-' . $product_id,
					'lav_boost_services_new_name'                                      => $serviceProductName . ' (' . $currentProductName . ')',
					'lav_boost_services_main_product-' . $current_id                   => 'lav_boost_services_main_product-' . $current_id,
				);

				$keyItem = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

				wp_send_json( [
					'title' => esc_html__( 'Product has been added to your cart', 'up-sell-pro' ),
					'data'  => $cart_item_data,
					'key'   => $keyItem,
				] );
				wp_die( '0' );
			} else {
				wp_die( esc_html__( 'Access denied', 'up-sell-pro' ), esc_html__( 'Denied', 'up-sell-pro' ), 403 );
			}

		}

		wp_die( '0' );
	}

	function removeCartItemAjax() {
		if ( isset( $_POST['cart_item_key'] ) ) {
			if ( empty( $_POST['nonce'] ) || empty( $_POST['product_id'] ) ) {
				wp_die( '0' );
			}
			$cartKey    = strip_tags( $_POST['cart_item_key'] );
			$product_id = absint( wc_clean( $_POST['product_id'] ) );
			if ( check_ajax_referer( 'nonce-lav-boost-services', 'nonce', false ) ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( isset( $cart_item[ $cartKey ] ) && $cart_item['product_id'] == $product_id ) {
						WC()->cart->remove_cart_item( $cart_item_key );
						wp_send_json( [
							'title' => esc_html__( 'Product has been removed from your cart', 'up-sell-pro' ),
						] );
						break;
					}
				}
				wp_die();
			} else {
				wp_die( esc_html__( 'Access denied', 'up-sell-pro' ), esc_html__( 'Denied', 'up-sell-pro' ), 403 );
			}
		}
		wp_die( '0' );
	}

	public function getArgs() {
		return array(
			'posts_per_page' => $this->getValue( 'services_quantity' ),
			'orderby'        => 'date',
		);
	}


	public function getServices( $product_id, $services_data ) {
		$serviceValues = array();

		if ( empty( $product_id ) || empty( $services_data ) || ! is_array( $services_data ) ) {
			return array();
		}

		foreach ( $services_data as $service_data ) {
			if ( $service_data['services_relation_data'] === 'tags' ) {
				$tag_ids = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );
				foreach ( $services_data as $service ) {
					if ( in_array( $service["services_main_tags"], $tag_ids ) ) {
						array_push( $serviceValues, array(
							'data'                 => ! empty( $service_data['services_relation_data'] ) ? $service_data['services_relation_data'] : 'tags',
							'terms'                => ! empty( $service['services_up_sell_tags'] ) ? $service['services_up_sell_tags'] : array(),
							'services_title'       => ! empty( $service['services_title'] ) ? $service['services_title'] : '',
							'services_description' => ! empty( $service['services_description'] ) ? $service['services_description'] : '',
						) );
					}
				}
			} else {
				$category_ids = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
				foreach ( $services_data as $service ) {
					if ( in_array( $service["services_main_category"], $category_ids ) ) {
						array_push( $serviceValues,
							array(
								'data'                 => ! empty( $service_data['services_relation_data'] ) ? $service_data['services_relation_data'] : 'categories',
								'terms'                => ! empty( $service['services_up_sell_categories'] ) ? $service['services_up_sell_categories'] : array(),
								'services_title'       => ! empty( $service['services_title'] ) ? $service['services_title'] : '',
								'services_description' => ! empty( $service['services_description'] ) ? $service['services_description'] : '',
							)
						);
					}
				}
			}
		}

		return ! empty( $serviceValues[0] ) ? $serviceValues[0] : array();
	}


	public function hasChecked( $current_id, $serviceProduct ) {
		$isInCartKey = 'lav_boost_services_is_in_cart-' . $current_id . '-' . $serviceProduct;
		$isInCart    = null;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item[ $isInCartKey ] ) ) {
				$isInCart = 'checked';
				break;
			}
		}

		return $isInCart;
	}


	public function render( $arguments = '' ) {
		global $product;

		if ( $product->is_visible() && ! $product->is_in_stock() ) {
			return;
		}
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$args             = $this->getArgs();
		$args['id']       = $product->get_id();
		$args['services'] = $this->getServices( $product->get_id(), $this->getValue( 'services_items' ) );

		if ( ! is_array( $args['services'] ) || empty( $args['services'] ) ) {
			return;
		}

		$args['type']  = ! empty( $args['services']['data'] ) ? $args['services']['data'] : 'tags';
		$args['terms'] = ! empty( $args['services']['terms'] ) ? $args['services']['terms'] : array();

		$provider = new LavBoostDataLoader();
		$loop     = $provider->getData( 'services', $args );
		?>

		<?php if ( is_object( $loop ) && $loop->have_posts() ): ?>
            <section class="lav-boost service-products lav-boost-service-products">
				<?php if ( ! empty( $args['services']['services_title'] ) ): ?>
                    <h5 class="up-sell-products-title">
						<?php echo esc_html( $args['services']['services_title'] ); ?>
                    </h5>
				<?php endif; ?>
				<?php if ( ! empty( $args['services']['services_description'] ) ): ?>
                    <p class="up-sell-products-desc">
						<?php echo esc_html( $args['services']['services_description'] ); ?>
                    </p>
				<?php endif; ?>
                <div class="services-list">
					<?php
					while ( $loop->have_posts() ) : $loop->the_post();
						$_product = wc_get_product( get_the_ID() );
						?>
						<?php if ( $_product->is_in_stock() ): ?>
                            <div class="services-list-item">
                                <div class="title">
                                    <input <?php echo esc_attr( $this->hasChecked( $product->get_id(), $_product->get_id() ) ); ?>
                                            type="checkbox"
                                            data-product-id="<?php echo esc_attr( $_product->get_id() ); ?>"
                                            data-current-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
                                            class="service-checkbox" name="service-checkbox"
                                            id="service-checkbox-<?php echo esc_attr( $_product->get_id() ); ?>"/>
                                    <label for="service-checkbox-<?php echo esc_attr( $_product->get_id() ); ?>">
										<?php echo wp_kses_post( $_product->get_name() ); ?>
                                    </label>
                                    <div class="tooltip-container">
                                        <div class="tooltip">
                                            <div class="tooltip-icon">?</div>
                                            <div class="tooltiptext"><?php echo wp_kses_post( $_product->get_description() ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'service-price' ) ); ?>">
									<?php echo $_product->get_price_html(); ?>
                                </p>
                            </div>
						<?php endif; ?>
					<?php
					endwhile;
					wp_reset_query();
					?>
                </div>
            </section>
		<?php endif; ?>
		<?php
	}

	public function getFields(): array {
		return array(
			'name'   => 'services',
			'title'  => esc_html__( 'Services', 'up-sell-pro' ),
			'icon'   => 'fas fa-tools',
			'fields' => array(
				array(
					'id'       => 'services_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable services', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable services on the single product page', 'up-sell-pro' ),
					'default'  => '0',
				),

				array(
					'id'         => 'services_name_change',
					'type'       => 'switcher',
					'title'      => esc_html__( 'Services names', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Enable\Disable services name changing', 'up-sell-pro' ),
					'default'    => '0',
					'dependency' => array( 'services_enable', '==', '1', '', 'visible' ),
				),
				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'You can add up to 2 services if you need more services so please buy paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array( 'services_enable', '==', '1' ),
				),

				array(
					'id'         => 'services_quantity',
					'type'       => 'slider',
					'title'      => esc_html__( 'Quantity of Products', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up the max number of services on a single product page', 'up-sell-pro' ),
					'default'    => 1,
					'min'        => 1,
					'max'        => 2,
					'step'       => 1,
					'dependency' => array( 'services_enable', '==', '1', '', 'visible' ),
				),

				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'You can add only one condition if you need more conditions so please buy paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array( 'services_enable', '==', '1' ),
				),

				array(
					'id'           => 'services_items',
					'type'         => 'repeater',
					'max'          => 1,
					'title'        => esc_html__( 'Conditions', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Choose products for services', 'up-sell-pro' ),
					'button_title' => esc_html__( 'Add service', 'up-sell-pro' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'services_title',
							'type'        => 'text',
							'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
							'default'     => esc_html__( 'Recommended services', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),
						array(
							'id'          => 'services_description',
							'type'        => 'textarea',
							'title'       => esc_html__( 'Section description', 'up-sell-pro' ),
							'default'     => esc_html__( 'Helping with installation and set up', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),
						array(
							'id'       => 'services_relation_data',
							'type'     => 'select',
							'title'    => esc_html__( 'Relation data', 'up-sell-pro' ),
							'settings' => array( 'width' => '50%' ),
							'options'  => array(
								'tags'       => esc_html__( 'Tags', 'up-sell-pro' ),
								'categories' => esc_html__( 'Categories', 'up-sell-pro' ),
							),
							'subtitle' => esc_html__( 'Set up which data use for services items', 'up-sell-pro' ),
							'default'  => 'categories',
							'chosen'   => true,
						),
						array(
							'id'          => 'services_main_category',
							'type'        => 'select',
							'title'       => esc_html__( 'Main category', 'up-sell-pro' ),
							'chosen'      => true,
							'placeholder' => esc_html__( 'Select category', 'up-sell-pro' ),
							'options'     => 'categories',
							'settings'    => array( 'width' => '50%' ),
							'query_args'  => array(
								'taxonomy' => 'product_cat',
							),
							'dependency'  => array( 'services_relation_data', '==', 'categories' ),
						),
						array(
							'id'          => 'services_up_sell_categories',
							'type'        => 'select',
							'title'       => esc_html__( 'Related categories', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Select categories', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'sortable'    => true,
							'width'       => '250px',
							'options'     => 'categories',
							'query_args'  => array(
								'taxonomy' => 'product_cat',
							),
							'dependency'  => array( 'services_relation_data', '==', 'categories' ),
						),

						array(
							'id'          => 'services_main_tags',
							'type'        => 'select',
							'title'       => esc_html__( 'Main tag', 'up-sell-pro' ),
							'chosen'      => true,
							'placeholder' => esc_html__( 'Select tag', 'up-sell-pro' ),
							'options'     => 'tags',
							'settings'    => array( 'width' => '50%' ),
							'query_args'  => array(
								'taxonomy' => 'product_tag',
							),
							'dependency'  => array( 'services_relation_data', '==', 'tags' ),
						),

						array(
							'id'          => 'services_up_sell_tags',
							'type'        => 'select',
							'title'       => esc_html__( 'Related tags', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Select tags', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'sortable'    => true,
							'options'     => 'tags',
							'query_args'  => array(
								'taxonomy' => 'product_tag',
							),
							'dependency'  => array( 'services_relation_data', '==', 'tags' ),
						),

						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
					),
					'dependency'   => array( 'services_enable', '==', '1', '', 'visible' ),
				),

			)
		);
	}
}
