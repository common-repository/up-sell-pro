<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use traits\TLavBoostSingleton;

class LavBoostModuleGiftProduct extends LavBoostModule {
	use TLavBoostSingleton;

	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'gift_product_enable' ) && !empty($this->getValue( 'gift_product_item' )) ) {
			add_action( 'woocommerce_after_single_product', array( $this, 'render' ), 10 );
			add_action( 'woocommerce_add_to_cart', array( $this, 'addToCartAction' ), 20 );
			add_action( 'woocommerce_update_cart_action_cart_updated', array( $this, 'updateCartAction' ), 20 );
			add_action( 'woocommerce_cart_item_removed', array( $this, 'removeItemAction' ), 10, 2 );
		}
	}

	public function getArgs() {
		return $this->getValue( 'gift_product_item' );
	}

	public function getTagColor( $cartTotal, $giftValue ) {

		if ( $cartTotal >= $giftValue ) {
			return 'green';
		}

		if ( empty( $cartTotal ) || empty( $giftValue ) ) {
			return 'red';
		}

		if ( $giftValue / 2 <= $cartTotal ) {
			return 'yellow';
		} else {
			return 'red';
		}
	}

	public function isGiftInCart( $id ) {
		$inCart = false;
		foreach ( WC()->cart->get_cart() as $cartItem ) {
			$productInCart = $cartItem['product_id'];
			if ( in_array( $productInCart, array( $id ) ) ) {
				$inCart = true;
				break;
			}
		}

		return $inCart;
	}


	public function addToCartAction() {
		$giftId = $this->getValue( 'gift_product_item' );
		if ( empty( $giftId ) ) {
			return;
		}
		$product = wc_get_product($giftId);
		if ( $product->get_price() > 0 || !$product->is_type('simple')  || !$product->is_in_stock()) {
			return;
		}

		if ( $this->isGiftInCart( $giftId ) ) {
			return;
		}
		WC()->cart->calculate_totals();
		// add gift if applicable
		if ( WC()->cart->cart_contents_total >= $this->getValue( 'gift_product_value' ) ) {
			WC()->cart->add_to_cart( $giftId );
		}
	}

	public function updateCartAction() {
		$giftId    = $this->getValue( 'gift_product_item' );
		$giftValue = $this->getValue( 'gift_product_value' );

		if ( empty( $giftId ) || empty( $giftValue ) ) {
			return;
		}
		$product = wc_get_product($giftId);
		if ( $product->get_price() > 0 ||  !$product->is_type('simple') || !$product->is_in_stock()) {
			return;
		}
		WC()->cart->calculate_totals();
		// remove\add if less\more
		if ( WC()->cart->cart_contents_total >= $this->getValue( 'gift_product_value' ) ) {
			if ( $this->isGiftInCart( $giftId ) ) {
				return;
			}
			WC()->cart->add_to_cart( $giftId );
		} else {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( $cart_item['product_id'] == $giftId ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}
	}

	public function removeItemAction() {
		$giftId    = $this->getValue( 'gift_product_item' );
		$giftValue = $this->getValue( 'gift_product_value' );

		if ( empty( $giftId ) || empty( $giftValue ) ) {
			return;
		}

		WC()->cart->calculate_totals();
		// remove if less
		if ( WC()->cart->cart_contents_total < $this->getValue( 'gift_product_value' ) && $this->isGiftInCart( $giftId ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( $cart_item['product_id'] == $giftId ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}
	}

	public function render( $arguments = '' ) {
		$place = $this->getValue( 'gift_product_place' )
			? $this->getValue( 'gift_product_place' )
			: 'right';
		WC()->cart->calculate_totals();

		$cartTotal = WC()->cart->cart_contents_total;
		$giftValue = $this->getValue( 'gift_product_value' );

		if ( empty( $this->getValue( 'gift_product_item' ) ) || $cartTotal >= $giftValue ) {
			return;
		}

		$title    = $this->getValue( 'gift_product_title' );
		$product  = wc_get_product( $this->getArgs() );
		$tagColor = $this->getTagColor( $cartTotal, $giftValue );

		if ( $product->get_price() > 0 ||  !$product->is_type('simple') || !$product->is_in_stock()) {
			return;
		}

		if ( $tagColor == 'yellow' ) {
			$title = $this->getValue( 'gift_product_title_middle' );
		}
		?>
        <div id="up-sell-gift-action-btn" class="lav-boost lav-boost-gift action-button <?php echo esc_attr( $place . ' ' . $tagColor ); ?>">
            <img class="action-button-icon"
                 src="<?php echo esc_url( LAV_BOOST_URL . '/public/img/gift-svgrepo-com.svg' ); ?>" alt="Gift icon">
            <div class="action-button-content">
                <div class="action-button-content-inner">
	                <?php if ( !empty($title) ): ?>
                        <h2 class="p-sell-gift-title"><?php echo esc_html( $title ); ?></h2>
	                <?php endif; ?>
                    <div class="card gift-item related-product-id-<?php echo esc_attr( $this->getValue( 'gift_product_item' ) ); ?>">
						<?php echo $product->get_image( 'medium' ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <div class="card-desc">
                            <h4 class="up-sell-card-title">
								<?php echo wp_kses_post( $product->get_name() ); ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}


	public function getFields(): array {
		return array(
			'name'   => 'gift_product',
			'title'  => esc_html__( 'Gift Product', 'up-sell-pro' ),
			'icon'   => 'fas fa-gift',
			'fields' => array(
				array(
					'id'       => 'gift_product_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable Gift Product', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable Gift Product. Note: Add Gift product to your shop with 0 price -> Enable Catalog visibility: Hidden', 'up-sell-pro' ),
					'default'  => '0',
					'help'     => esc_html__( 'It shows Gift Products on a single product page', 'up-sell-pro' ),
				),

				array(
					'id'          => 'gift_product_item',
					'type'        => 'select',
					'title'       => esc_html__( 'Gift Product', 'up-sell-pro' ),
					'subtitle'    => esc_html__( 'Choose your gift product', 'up-sell-pro' ),
					'chosen'      => true,
					'ajax'        => true,
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
					'dependency'  => array( 'gift_product_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'gift_product_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Gift product place', 'up-sell-pro' ),
					'options'    => array(
						'left'  => esc_html__( 'Left', 'up-sell-pro' ),
						'right' => esc_html__( 'Right', 'up-sell-pro' ),
					),
					'default'    => 'right',
					'dependency' => array( 'gift_product_enable', '==', '1', '', 'visible' ),
					'subtitle'   => esc_html__( 'The place to put the product section on a single product page', 'up-sell-pro' ),
				),

				array(
					'id'         => 'gift_product_value',
					'type'       => 'number',
					'title'      => esc_html__( 'Cart value for gift', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Cart value to get free gift', 'up-sell-pro' ),
					'default'    => 50,
					'dependency' => array( 'gift_product_enable', '==', '1', '', 'visible' ),
				),
				// Style settings
				array(
					'type'    => 'heading',
					'content' => esc_html__( 'Headings', 'up-sell-pro' ),
				),
				array(
					'id'          => 'gift_product_title',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'subtitle'    => esc_html__( 'Title if total cart is less than 50% of gift value', 'up-sell-pro' ),
					'default'     => esc_html__( 'Buying together often', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'gift_product_enable', '==', '1', '', 'visible' ),
					'help'        => esc_html__( 'Title if the total cart is less than 50% of gift value', 'up-sell-pro' ),
				),

				array(
					'id'          => 'gift_product_title_middle',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'subtitle'    => esc_html__( 'Title if total cart is more than 50% of gift value', 'up-sell-pro' ),
					'default'     => esc_html__( 'Buying together often', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'gift_product_enable', '==', '1', '', 'visible' ),
					'help'        => esc_html__( 'Title if the total cart is more than 50% of gift value', 'up-sell-pro' ),
				),

			)
		);
	}
}
