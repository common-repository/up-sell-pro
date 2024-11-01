<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModulePopUpRelated extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'pop_enable_related_products' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 99 );
			add_filter( 'body_class', array( $this, 'addBodyClasses' ) );
			add_action( 'wp_ajax_popUpResponse', array( $this, 'render' ) );
			add_action( 'wp_ajax_nopriv_popUpResponse', array( $this, 'render' ) );
		}
	}


	public function localize() {
		wp_localize_script( 'lav-boost', 'lavBoostPopUp', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nonce-lav-boost' )
		) );
		wp_enqueue_script( 'popupS', LAV_BOOST_URL . 'public/js/popupS.js', array(), UP_SELL_PRO_VERSION, true );
	}

	function addBodyClasses( $classes ) {

		if ( function_exists( 'get_option' ) ) {
			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				$classes[] = 'lav-boost-redirect-after-add';
			}
			if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) && 'no' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				$classes[] = 'lav-boost-ajax';
			} else {
				$classes[] = 'lav-boost-not-ajax';
			}

			return $classes;
		}

	}

	public function getArgs() {
		return array(
			'posts_per_page' => $this->getValue( 'pop_additional_products' ) !== null
				? $this->getValue( 'pop_additional_products' )
				: 2,
			'orderby'        => $this->getValue( 'pop_relation_order' ) !== null
				? $this->getValue( 'pop_relation_order' )
				: 'rand',
			'add_random'     => $this->getValue( 'pop_add_if_empty' ) == 'yes',
			'type'           => $this->getValue( 'pop_relation_priority' ),
			'title'          => $this->getValue( 'pop_add_bundle' ),
			'cart'           => $this->getValue( 'pop_cart_link' ),
			'checkout'       => $this->getValue( 'pop_checkout_link' ),
		);
	}

	public function render( $arguments = '' ) {

		if ( empty( $_POST['nonce'] ) ) {
			wp_die( '0' );
		}

		if ( empty( $_POST['id'] ) ) {
			wp_die( '0' );
		}

		$args       = $this->getArgs();
		$args['id'] = sanitize_text_field( $_POST['id'] );
		$row        = $this->getRow( $args );
		ob_end_clean();

		if ( check_ajax_referer( 'nonce-lav-boost', 'nonce', false ) ) {
			wp_send_json( [
				'markup'   => $row,
				'title'    => ! empty( $this->getValue( 'pop_add_bundle' ) )
					? $this->getValue( 'pop_add_bundle' )
					: esc_html__( 'Product has been added', 'up-sell-pro' ),
				'continue' => esc_html__( 'Continue shopping', 'up-sell-pro' )
			] );
			wp_die();
		} else {
			wp_die( esc_html__( 'Access denied', 'up-sell-pro' ), esc_html__( 'Denied', 'up-sell-pro' ), 403 );
		}
	}

	public function getRow( $args ) {
		$provider = new LavBoostDataLoader();
		$loop     = $provider->getData( $this->getValue( 'pop_relation_priority' ), $args );

		if ( $this->getValue( 'pop_add_if_empty' ) == 'yes' && ! $loop->have_posts() ) {
			$loop = $provider->getData( 'random', $args );
		}
		$cart_page     = ! empty( $args['cart'] ) ? $args['cart'] : esc_url( wc_get_page_permalink( 'cart' ) );
		$checkout_page = ! empty( $args['checkout'] ) ? $args['checkout'] : esc_url( wc_get_page_permalink( 'checkout' ) );

		$message = sprintf( '<a href="%s" class="wc-checkout-page">%s</a>', $checkout_page, esc_html__( 'Checkout', 'up-sell-pro' ) );
		$message .= sprintf( '<a href="%s" class="wc-forward wc-cart-page">%s</a>', $cart_page, esc_html__( 'View cart', 'up-sell-pro' ) );

		if ( ! function_exists( 'woocommerce_template_loop_rating' ) && ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		ob_start();

		?>
		<?php if ( ! empty( $loop ) && $loop->have_posts() ): ?>
			<?php echo wp_kses( $message, $this->getAllowedTags() ); ?>
			<?php if ( ! empty( $this->getValue('pop_add_bundle_desc') ) ): ?>
                <h4 class="lav-pop-up-title"><?php echo esc_html( $this->getValue('pop_add_bundle_desc') ); ?></h4>
			<?php endif; ?>
            <div class="section-products up-sell-products">
                <div class="container">
                    <div class="list-products product-columns-<?php echo esc_attr($args['posts_per_page']); ?>">
						<?php
						while ( $loop->have_posts() ) : $loop->the_post();
							global $post;
							$_product = wc_get_product( get_the_ID() );
							?>
                            <div class="product card related-product related-product-id-<?php echo esc_attr( get_the_ID() ); ?>"
                                 data-price="<?php echo esc_attr( $_product->get_price() ); ?>">
								<?php
								if ( $_product->get_sale_price() ) {
									echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'up-sell-pro' ) . '</span>', $post, $_product );
								}
								?>
                                <div class="top">
                                    <a href="<?php echo esc_url( get_permalink() ); ?>">
										<?php echo $_product->get_image( 'medium' ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </a>
                                    <div class="add-to-cart-overlay">
		                                <?php woocommerce_template_loop_add_to_cart(); ?>
                                    </div>
                                </div>
                                <div class="bottom">
                                    <a class="up-sell-card-title" href="<?php echo esc_url( get_permalink() ); ?>">
                                        <h4 class="up-sell-card-title">
											<?php echo wp_kses_post( $_product->get_name() ); ?>
                                        </h4>
                                    </a>
                                    <div class="rating-info">
										<?php woocommerce_template_loop_rating(); ?>
                                    </div>
                                    <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'card-price' ) ); ?>">
										<?php echo $_product->get_price_html(); ?>
                                    </p>
                                </div>
                            </div>
						<?php
						endwhile;
						wp_reset_query();
						?>
                    </div>
                </div>
            </div>
		<?php endif; ?>
		<?php
		return ob_get_contents();
	}

	public function getFields(): array {
		return array(
			'name'   => 'pop',
			'title'  => esc_html__( 'Pop Up', 'up-sell-pro' ),
			'icon'   => 'far fa-object-group',
			'fields' => array(
				array(
					'id'       => 'pop_enable_related_products',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable Pop Up', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable related products Pop up after click add to cart on Shop page', 'up-sell-pro' ),
					'default'  => '1',
					'help'     => esc_html__( 'It shows Up-sell\Cross-sell products after click Add to cart button and help suggest to user relevant products on Shop Page', 'up-sell-pro' ),
				),

				array(
					'id'         => 'pop_additional_products',
					'type'       => 'slider',
					'title'      => esc_html__( 'Quantity of Products', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up the max number of related products for Pop up', 'up-sell-pro' ),
					'default'    => 2,
					'min'        => 1,
					'max'        => 3,
					'step'       => 1,
					'dependency' => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'pop_add_bundle',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Product has been added to your cart', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'pop_add_bundle_desc',
					'type'        => 'text',
					'title'       => esc_html__( 'Section Description', 'up-sell-pro' ),
					'default'     => esc_html__( 'Often buy together', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'pop_cart_link',
					'type'        => 'text',
					'title'       => esc_html__( 'Cart link', 'up-sell-pro' ),
					'default'     => '/cart/',
					'placeholder' => esc_html__( 'Put link text here', 'up-sell-pro' ),
					'dependency'  => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'pop_checkout_link',
					'type'        => 'text',
					'title'       => esc_html__( 'Checkout link', 'up-sell-pro' ),
					'default'     => '/checkout/',
					'placeholder' => esc_html__( 'Put link text here', 'up-sell-pro' ),
					'dependency'  => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'pop_relation_priority',
					'type'       => 'select',
					'title'      => esc_html__( 'Relation data', 'up-sell-pro' ),
					'settings'   => array( 'width' => '50%' ),
					'options'    => array(
						'tags'       => esc_html__( 'Tags', 'up-sell-pro' ),
						'categories' => esc_html__( 'Categories', 'up-sell-pro' ),
						'viewed'     => esc_html__( 'Viewed', 'up-sell-pro' ),
					),
					'subtitle'   => esc_html__( 'Set up which data use for related products', 'up-sell-pro' ),
					'default'    => 'categories',
					'chosen'     => true,
					'dependency' => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'pop_add_if_empty',
					'type'       => 'radio',
					'title'      => esc_html__( 'Add random relations', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Add products if relations are empty or didn\'t match', 'up-sell-pro' ),
					'options'    => array(
						'yes' => esc_html__( 'Yes', 'up-sell-pro' ),
						'no'  => esc_html__( 'No', 'up-sell-pro' ),
					),
					'default'    => 'yes',
					'dependency' => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'pop_relation_order',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Order by', 'up-sell-pro' ),
					'options'    => array(
						'rand'     => esc_html__( 'Random', 'up-sell-pro' ),
						'date'     => esc_html__( 'Date', 'up-sell-pro' ),
						'modified' => esc_html__( 'Modified', 'up-sell-pro' ),
					),
					'default'    => 'rand',
					'dependency' => array( 'pop_enable_related_products', '==', '1', '', 'visible' ),
				),
			)
		);
	}
}
