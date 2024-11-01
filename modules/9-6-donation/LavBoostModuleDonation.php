<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleDonation extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'donation_enable' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 99 );
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'render' ), 9999 );

			// AJAX action to add a product to cart
			add_action( 'wp_ajax_donate_add_to_cart', array( $this, 'addToCartAjax' ) );
			add_action( 'wp_ajax_nopriv_donate_add_to_cart', array( $this, 'addToCartAjax' ) );

			// AJAX action to remove a product from cart
			add_action( 'wp_ajax_donate_remove_cart_item', array( $this, 'removeCartItemAjax' ) );
			add_action( 'wp_ajax_nopriv_donate_remove_cart_item', array( $this, 'removeCartItemAjax' ) );
		}
	}

	public function localize() {
		wp_localize_script( 'lav-boost', 'lavBoostDonate', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nonce-lav-boost-donate' )
		) );
	}

	public function addToCartAjax() {
		if ( isset( $_POST['product_id'] ) ) {
			if ( empty( $_POST['nonce'] ) || empty( $_POST['product_id'] ) ) {
				wp_die( '0' );
			}

			if ( check_ajax_referer( 'nonce-lav-boost-donate', 'nonce', false ) ) {
				$product_id     = absint( wc_clean( $_POST['product_id'] ) );
				$cart_item_data = array(
					'lav_boost_donate_key'        => 'lav_boost_donate_meta_key',
					'lav_boost_donate_is_in_cart' => 'lav_boost_donate_is_in_cart',
				);

				$keyItem = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

				WC()->cart->calculate_totals();
				wp_send_json( [
					'title' => esc_html__( 'Donate has been added to your cart', 'up-sell-pro' ),
					'data'  => $cart_item_data,
					'key'   => $keyItem
				] );
				wp_die();
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
			$product_id = absint( wc_clean( $_POST['product_id'] ) );
			if ( check_ajax_referer( 'nonce-lav-boost-donate', 'nonce', false ) ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( isset( $cart_item['lav_boost_donate_is_in_cart'] ) && $cart_item['product_id'] == $product_id ) {
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
		$donationProductsIds = array();
		$products            = array();
		$donationProducts    = $this->getValue( 'donation_items' );

		if ( is_array( $donationProducts ) && ! empty( $donationProducts ) ) {
			foreach ( $donationProducts as $donation ) {
				array_push( $donationProductsIds, $donation['donation_product'] );
				if ( function_exists( 'wc_get_product' ) ) {
					array_push( $products, wc_get_product( $donation['donation_product'] ) );
				}

			}
		}

		return array( 'ids' => $donationProductsIds, 'products' => $products );
	}

	public function render( $arguments = '' ) {

		$product_ids = $this->getArgs();

		if ( class_exists( 'WC' ) && empty( $product_ids ) ) {
			return;
		}

		$donationProducts = $this->getValue( 'donation_items' );
		$items            = array();
		if ( is_array( $donationProducts ) && ! empty( $donationProducts ) ) {
			foreach ( $donationProducts as $donation ) {
				$title   = '';
				$ability = false;
				$inCart = '';
				$id      = ! empty( $donation['donation_product'] ) ? $donation['donation_product'] : '';

				if ( empty( $id ) ) {
					continue;
				}

				if ( function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $id );
					$title   = ! empty( $product->get_name() ) ? $product->get_name() : $product->get_price();
					$ability = ( $product && $product->is_in_stock() && $product->get_price() > 0 );
				}
				$button = ! empty( $donation['donation_button_text'] )
					? $donation['donation_button_text']
					: $title;

				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					if (isset($cart_item['lav_boost_donate_is_in_cart']) && $cart_item['product_id'] == $id) {
						$inCart = 'checked';
						break;
					}
				}

				array_push( $items, array( 'id' => $id, 'button' => $button, 'ability' => $ability, 'in_cart' => $inCart) );
			}
		}
		?>
		<?php if ( ! empty( $items ) ): ?>
            <div class="lav-boost lav-boost-donation">
				<?php if ( $this->getValue( 'donation_title' ) ): ?>
                    <h2 class="donation-title">
						<?php echo esc_html( $this->getValue( 'donation_title' ) ); ?>
                    </h2>
				<?php endif; ?>
				<?php if ( $this->getValue( 'donation_description' ) ): ?>
                    <p class="donation-description">
						<?php echo esc_html( $this->getValue( 'donation_description' ) ); ?>
                    </p>
				<?php endif; ?>
				<?php if ( is_array( $items ) && ! empty( $items ) ): ?>
                    <div class="lav-boost-donation-buttons">
                    <?php foreach ( $items as $item ): ?>
                        <?php if ( ! empty( $item['ability'] ) ): ?>
                        <div class="donation-item">
                            <input <?php echo esc_attr( $item['in_cart'] ); ?>
                                    type="checkbox" data-product-id="<?php echo esc_attr( $item['id'] ); ?>"
                                    class="donate-checkbox" name="donate-checkbox"
                                    id="donate-checkbox-<?php echo esc_attr( $item['id'] ); ?>"/>
                            <label for="donate-checkbox-<?php echo esc_attr( $item['id'] ); ?>">
		                        <?php echo esc_html( $item['button'] ); ?>
                            </label>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
				<?php endif; ?>
            </div>
		<?php endif; ?>
		<?php
	}

	public function getFields(): array {
		return array(
			'name'   => 'donation_buttons',
			'title'  => esc_html__( 'Donation', 'up-sell-pro' ),
			'icon'   => 'fas fa-donate',
			'fields' => array(
				array(
					'id'       => 'donation_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable donation', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable donation buttons on checkout page', 'up-sell-pro' ),
					'default'  => '0',
				),
				array(
					'id'          => 'donation_title',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Make a donation?', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
				),
				array(
					'id'          => 'donation_description',
					'type'        => 'textarea',
					'title'       => esc_html__( 'Section description', 'up-sell-pro' ),
					'default'     => esc_html__( 'Helping for kids', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
				),

				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'You can add only one donate if you need more donations so please buy paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array( 'donation_enable', '==', '1', ),
				),

				array(
					'id'           => 'donation_items',
					'type'         => 'repeater',
					'max'          => 1,
					'title'        => esc_html__( 'Donate products', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Choose products for donations', 'up-sell-pro' ),
					'button_title' => esc_html__( 'Add Donation', 'up-sell-pro' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'donation_product',
							'type'        => 'select',
							'title'       => esc_html__( 'Products', 'up-sell-pro' ),
							'subtitle'    => esc_html__( 'Choose', 'up-sell-pro' ),
							'chosen'      => true,
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
								),
							),
						),

						array(
							'id'          => 'donation_button_text',
							'type'        => 'text',
							'title'       => esc_html__( 'Title', 'up-sell-pro' ),
							'subtitle'    => esc_html__( 'Custom title(default is product title)', 'up-sell-pro' ),
							'default'     => esc_html__( 'Donate', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
					),
					'dependency'   => array( 'donation_enable', '==', '1', '', 'visible' ),
				),

			)
		);
	}
}
