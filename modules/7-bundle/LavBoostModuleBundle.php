<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleBundle extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		$place = $this->getValue( 'bundle_product_relation_place' )
			? $this->getValue( 'bundle_product_relation_place' )
			: 'woocommerce_after_single_product_summary';

		if ( $this->getValue( 'bundle_product_enable_related_products' ) ) {
			add_action( 'wp_loaded', array( $this, 'addBundleToCart' ), 11 );
			add_action( $place, array( $this, 'render' ), 10 );
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'addDiscountForCart' ) );
		}
	}

	function addDiscountForCart( $cart ) {

		$bundles = $this->getValue( 'bundle_product_bundles' );

		if ( empty( $bundles ) || ! is_array( $bundles ) ) {
			return;
		}

		// Find the offers applicable.
		foreach ( $bundles as $bundle ) {
			if ( ! empty( $bundle['bundle-products'] ) ) {
				$this->apply_discount_for_offer( $bundle, $cart );
			}
		}
	}

	public function getDiscount( $type, $value, $price ) {

		if ( $type != 'percent' ) {
			if ( $value >= $price ) {
				return $price;
			} else {
				return $value;
			}
		}

		if ( $value >= 100 ) {
			return $price;
		}

		if ( $value > 0 ) {
			return $price * $value / 100;
		}
	}

	public function isBundleInCart( $args, $cart ) {
		$bundle_count  = 0; // initialize count of bundles found in cart
		$product_count = 0; // initialize count of products in the bundle found in cart
		$bundleIds     = !empty($args['bundle-products']) ? $args['bundle-products'] : array();
		$mainProduct   = !empty($args['main-product']) ? $args['main-product'] : null;

		// loop through cart items
		foreach ( $cart->get_cart() as $cart_item ) {
			$product_id = $cart_item['product_id'];
			if ( in_array( $product_id, $bundleIds ) ) {
				$product_count ++;
				if ( $product_count == count( $bundleIds ) ) { // all products in bundle are in cart
					$bundle_count ++;
					$product_count = 0;
				}
			} else {
				$product_count = 0; // reset product count if not in bundle
			}
		}
		$bundle_products_in_cart = true;
		if ( ! $cart->find_product_in_cart( $cart->generate_cart_id( $mainProduct ) ) ) {
			$bundle_products_in_cart = false;
		}

		if ( $bundle_count > 0 && $bundle_products_in_cart ) {
			return true;
		} else {
			return false;
		}

	}


	public function apply_discount_for_offer( $args, $cart ) {
		$discountType   = $args['bundle_product_discount_type'];
		$discountValue  = $args['bundle_product_discount_value'];
		$discountSymbol = $discountType == 'fixed' ? get_woocommerce_currency_symbol() : '%';
		$bundleIds      = $args['bundle-products'];
		$discountName   = ! empty( $args['bundle_product_discount_name'] )
			? $args['bundle_product_discount_name']
			: sprintf( __( '- %1$s%2$s Bundle Discount', 'up-sell-pro' ), number_format($discountValue, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()), $discountSymbol );

		// apply discount if bundle is found and not already applied
		if ( $this->isBundleInCart( $args, $cart ) ) {
			$bundle_price = 0;

			// Calculate the total price of the bundle
			foreach ( $bundleIds as $product_id ) {
				$bundle_price += floatval(get_post_meta( $product_id, '_price', true ));
			}

			// Calculate the discount amount
			$discount_amount = $this->getDiscount( $discountType, $discountValue, $bundle_price );
			$cart->add_fee( $discountName, - $discount_amount );
		}
	}

	public static function addBundleToCart() {
		if ( empty( $_REQUEST['add-bundle-to-cart'] ) || false === strpos( $_REQUEST['add-bundle-to-cart'], ',' ) ) {
			return null;
		}
		$ids                = explode( ',', sanitize_text_field( $_REQUEST['add-bundle-to-cart'] ) );
		$bundleIds          = array_map( 'absint', array_filter( $ids ) );
		$redirect_after_add = get_option( 'woocommerce_cart_redirect_after_add' );
		$message            = '';

		$added_all_to_cart          = true;
		$products_not_added_to_cart = array();

		foreach ( $bundleIds as $product_add_to_cart ) {
			$product     = wc_get_product( $product_add_to_cart );
			$quantity    = 1;
			$add_to_cart = false;


			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_add_to_cart, $quantity, 0 );

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
		$current_url = remove_query_arg( 'add-bundle-to-cart', $current_url );
		wp_redirect( $current_url );
		exit;
	}

	public function getArgs() {
		return array(
			'posts_per_page' => is_array( $this->getValue( 'bundle-products' ) )
				? count( $this->getValue( 'bundle-products' ) )
				: - 1,
			'orderby'        => 'date',
			'post__in'       => array(),
			'add_random'     => false,
			'type'           => false,
		);
	}

	public function getBundles( $args, $currentProduct ) {
		$bundles = array();
		if ( ! is_array( $args ) && ! in_array( $currentProduct, $args ) ) {
			return false;
		}

		if ( ! empty( $args ) ) {
			foreach ( $args as $arg ) {
				if ( ! empty( $arg['main-product'] && $arg['main-product'] == $currentProduct ) ) {
					array_push( $bundles, $arg );
				}
			}
		}

		return $bundles;
	}

	public function getPriceWithDiscount( $bundle, $bundlePrice, $mainProductPrice ) {
		$discountType  = $bundle['bundle_product_discount_type'];
		$discountValue = $bundle['bundle_product_discount_value'];
		// Calculate the discount amount
		$discountAmount = $this->getDiscount( $discountType, $discountValue, $bundlePrice );
		return number_format(($bundlePrice + $mainProductPrice) - $discountAmount, wc_get_price_decimals(), '.', '');
	}

	public function render( $arguments = '' ) {
		global $product, $post;
		$currentProduct = $product->get_id();

		if ( empty( $this->getValue( 'bundle_product_bundles' ) ) ) {
			return;
		}

		$bundles = $this->getBundles( $this->getValue( 'bundle_product_bundles' ), $currentProduct );

		if ( empty( $bundles ) || ! is_array( $bundles ) ) {
			return;
		}
		$buttonText = !empty($this->getValue( 'bundle_product_add_to_cart' ))
			? $this->getValue( 'bundle_product_add_to_cart' )
			: esc_html__('Add to cart','up-sell-pro');
		if ( !function_exists( 'woocommerce_template_loop_rating' ) && !function_exists( 'wc_get_product' ) ){
		    return;
        }
		?>
		<?php foreach ( $bundles as $bundle ): ?>
				<?php if ( ! $this->isBundleInCart( $bundle, WC()->cart ) && !empty($bundle['bundle-products']) ): ?>
					<?php
					$args             = $this->getArgs();
					$provider         = new LavBoostDataLoader();
					$args['id']       = $product->get_id();

					$args['post__in'] = $bundle['bundle-products'];
					$loop             = $provider->getData( 'bundle', $args );
					$fullPrice        = $product->get_price();
					$discountPrice    = 0;
					$relatedIDs       = [ $product->get_id() ];

                    ?>
					<?php if ( $loop->have_posts() ): ?>
                    <section class="lav-boost section-products bundle">
                        <div class="pricing">
                            <div class="plan popular">
								<?php if ( $this->getValue( 'bundle_product_add_bundle' ) ): ?>
                                    <h2 class="badge-popular">
										<?php echo esc_html( $this->getValue( 'bundle_product_add_bundle' ) ); ?>
                                    </h2>
								<?php endif; ?>
                                <div class="product card main-product"
                                     data-price="<?php echo floatval( $product->get_price() ); ?>">
                                    <div class="top">
										<?php echo $product->get_image( 'medium' ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php
										if ( $product->get_sale_price() ) {
											echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'up-sell-pro' ) . '</span>', $post, $product );
										}
										?>
                                    </div>
                                    <div class="bottom">
                                        <h4 class="up-sell-card-title"><?php echo wp_kses_post( $product->get_name() ); ?></h4>

                                        <div class="rating-info">
											<?php woocommerce_template_loop_rating(); ?>
                                        </div>
                                        <div class="rating-info">
		                                    <?php echo $product->get_short_description(); ?>
                                        </div>
                                        <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'card-price' ) ); ?>">
											<?php echo $product->get_price_html(); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="plus">
                                    <span>+</span>
                                </div>
                                 <ul class="related-products">
								<?php


								while ( $loop->have_posts() ) : $loop->the_post();
									global $post;
									$_product     = wc_get_product( get_the_ID() );
									$fullPrice    += floatval( $_product->get_price() );
									$discountPrice  += floatval( $_product->get_price() );
									array_push( $relatedIDs, get_the_ID() );
									?>

                                        <li class="related-product related-product-id-<?php echo esc_attr( get_the_ID() ); ?>"
                                            data-price="<?php echo floatval( $_product->get_price() ); ?>">
                                            <div class="top">
												<?php
												if ( $_product->get_sale_price() ) {
													echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'up-sell-pro' ) . '</span>', $post, $_product );
												}
												?>
                                                <a href="<?php echo esc_url( get_permalink() ); ?>">
													<?php echo $_product->get_image( 'medium' ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                </a>
                                            </div>
                                            <div class="bottom">
                                                <a class="up-sell-card-title"
                                                   href="<?php echo esc_url( get_permalink() ); ?>">
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
                                        </li>

								<?php
								endwhile;
								wp_reset_query();
								?>
                                </ul>
                                <div class="button-row">
                                    <a href="<?php echo get_permalink() . '?add-bundle-to-cart=' . implode( ',', $relatedIDs ); ?>" class="btn">
                                        <button type="button" name="add-to-cart" class="single_add_to_cart_button button alt">
											<?php echo esc_html( $buttonText ); ?>
                                        </button>
                                    </a>
                                    <div class="price-discount-desc">
	                                    <?php if ( $this->getValue( 'bundle_product_add_to_cart_desc' ) ): ?>
                                            <div class="bundle-full-price-line">
                                            <span class="price-desc">
                                                <?php echo esc_html( $this->getValue( 'bundle_product_add_to_cart_desc' ) ); ?>
                                            </span>
                                                <span class="price">
                                                <del aria-hidden="true">
                                                    <bdi>
                                                       <?php echo sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(),  number_format($fullPrice, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator())  ); ?>
                                                    </bdi>
                                                </del>
                                                <ins>
                                                   <?php echo sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $this->getPriceWithDiscount( $bundle, $discountPrice, $product->get_price() ) ); ?>
                                                </ins>
                                            </span>
                                            </div>
	                                    <?php endif; ?>
	                                    <?php if ( !empty($bundle['bundle_product_discount_message']) ): ?>
                                            <div class="discount">
			                                    <?php echo esc_html( $bundle['bundle_product_discount_message'] ); ?>
                                            </div>
	                                    <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </section>
				    <?php endif; ?>
                <?php endif; ?>
			<?php endforeach; ?>

		<?php
	}

	public function getFields(): array {
		return array(
			'name'   => 'bundle_product',
			'title'  => esc_html__( 'Bundle', 'up-sell-pro' ),
			'icon'   => 'fas fa-laptop',
			'fields' => array(
				array(
					'id'       => 'bundle_product_enable_related_products',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable bundles', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable bundles section on single product page', 'up-sell-pro' ),
					'default'  => '0',
				),

				array(
					'id'         => 'bundle_product_relation_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Bundle place', 'up-sell-pro' ),
					'options'    => array(
						'woocommerce_after_single_product'         => esc_html__( 'After product', 'up-sell-pro' ),
						'woocommerce_after_single_product_summary' => esc_html__( 'After summary', 'up-sell-pro' ),
					),
					'dependency' => array( 'bundle_product_enable_related_products', '==', '1', '', 'visible' ),
					'default'    => 'woocommerce_after_single_product',
					'subtitle'   => esc_html__( 'Place to put bundles section on single product page', 'up-sell-pro' ),
				),


				array(
					'id'          => 'bundle_product_add_bundle',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Together cheaper', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'bundle_product_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'bundle_product_add_to_cart',
					'type'        => 'text',
					'title'       => esc_html__( 'Button text', 'up-sell-pro' ),
					'default'     => esc_html__( ' Add Bundle to Cart', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put text for button here', 'up-sell-pro' ),
					'dependency'  => array( 'bundle_product_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'bundle_product_add_to_cart_desc',
					'type'        => 'text',
					'title'       => esc_html__( 'Price description', 'up-sell-pro' ),
					'default'     => esc_html__( 'Total price: ', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put text here', 'up-sell-pro' ),
					'dependency'  => array( 'bundle_product_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'type'    => 'heading',
					'content' => esc_html__( 'Bundles', 'up-sell-pro' ),
				),

				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'You can add up to 3 bundles if you need more bundles so please buy paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array( 'bundle_product_enable_related_products', '==', '1', ),
				),

				array(
					'type'         => 'repeater',
					'id'           => 'bundle_product_bundles',
					'title'        => esc_html__( 'Bundles', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Set up the bundles', 'up-sell-pro' ),
					'max'          => 3,
					'button_title' => esc_html__( 'Add Bundle', 'up-sell-pro' ),
					'dependency'   => array( 'bundle_product_enable_related_products', '==', '1', '', 'visible' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'bundle_product_discount_name',
							'type'        => 'text',
							'title'       => esc_html__( 'Discount name', 'up-sell-pro' ),
							'default'     => esc_html__( 'Bundle discount', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put text here', 'up-sell-pro' ),
						),
						array(
							'id'          => 'main-product',
							'type'        => 'select',
							'title'       => esc_html__( 'Main product', 'up-sell-pro' ),
							'chosen'      => true,
							'settings'    => array( 'width' => '50%' ),
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
							'id'          => 'bundle-products',
							'type'        => 'select',
							'title'       => esc_html__( 'Bundle products', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Select products', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'sortable'    => true,
							'width'       => '250px',
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
							'id'      => 'bundle_product_discount_value',
							'type'    => 'number',
							'title'   => esc_html__( 'Discount value', 'up-sell-pro' ),
							'default' => 10,
							'step'    => 1,
						),

						array(
							'id'      => 'bundle_product_discount_type',
							'type'    => 'button_set',
							'title'   => esc_html__( 'Discount type', 'up-sell-pro' ),
							'options' => array(
								'fixed'   => esc_html__( 'Fixed', 'up-sell-pro' ),
								'percent' => esc_html__( 'Percent', 'up-sell-pro' ),
							),
							'default' => 'percent',
						),

						array(
							'id'          => 'bundle_product_discount_message',
							'type'        => 'text',
							'title'       => esc_html__( 'Discount message', 'up-sell-pro' ),
							'default'     => esc_html__( 'Save 10% when bought together', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put text here', 'up-sell-pro' ),
						),
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
					),
				),
			)
		);
	}
}
