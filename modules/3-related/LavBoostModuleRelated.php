<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleRelated extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		$place = $this->getValue( 'product_page_relation_place' )
			? $this->getValue( 'product_page_relation_place' )
			: 'woocommerce_after_single_product_summary';

		if ( $this->getValue( 'product_page_enable_related_products' ) ) {
			add_action( $place, array( $this, 'render' ), 10 );
		}
	}

	public function getArgs() {
		return array(
			'posts_per_page' => $this->getValue( 'product_page_additional_products' ) !== null
				? $this->getValue( 'product_page_additional_products' )
				: 2,
			'orderby'        => $this->getValue( 'product_page_relation_order' ) !== null
				? $this->getValue( 'product_page_relation_order' )
				: 'rand',
			'add_random'     => $this->getValue( 'product_page_add_if_empty' ) == 'yes',
			'type'           => $this->getValue( 'product_page_relation_priority' ),
		);
	}

	public function render( $arguments = '' ) {
		global $product, $post;

		if ( $product->is_visible() && ! $product->is_in_stock() ) {
			return;
		}

		$args       = $this->getArgs();
		$provider   = new LavBoostDataLoader();
		$args['id'] = $product->get_id();
		$loop       = $provider->getData( $this->getValue( 'product_page_relation_priority' ), $args );
		$fullPrice  = $product->get_price();

		$relatedIDs = [ $product->get_id() ];

		if ( $this->getValue( 'product_page_add_if_empty' ) == 'yes' && ! $loop->have_posts() ) {
			$loop = $provider->getData( 'random', $args );
		}

		$buttonText = !empty($this->getValue( 'product_page_add_to_cart' ))
            ? $this->getValue( 'product_page_add_to_cart' )
            : esc_html__('Add to cart','up-sell-pro')
		?>
		<?php if ( function_exists( 'woocommerce_template_loop_rating' ) && function_exists( 'wc_get_product' ) ): ?>
			<?php if ( $loop->have_posts() ): ?>
                <section class="lav-boost section-products up-sell-products lav-boost-related">
                    <div class="container">
						<?php if ( $this->getValue( 'product_page_add_bundle' ) ): ?>
                            <h2 class="up-sell-products-title">
								<?php echo esc_html( $this->getValue( 'product_page_add_bundle' ) ); ?>
                            </h2>
						<?php endif; ?>
                        <div class="list-products">
                            <div class="product card main-product" data-price="<?php echo $product->get_price(); ?>">
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
                                    <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'card-price' ) ); ?>">
										<?php echo $product->get_price_html(); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="plus">
                                <span>+</span>
                            </div>
							<?php
							while ( $loop->have_posts() ) : $loop->the_post();
								global $post;
								$_product  = wc_get_product( get_the_ID() );
								$fullPrice += floatval( $_product->get_price() );
								array_push( $relatedIDs, get_the_ID() );
								?>
                                <div class="product card related-product related-product-id-<?php echo esc_attr( get_the_ID() ); ?>"
                                     data-price="<?php echo $_product->get_price(); ?>">
                                    <div class="top">
                                        <input id="up-sell-check-id-<?php echo esc_attr( get_the_ID() ); ?>"
                                               type="checkbox" checked
                                               data-id="<?php echo esc_attr( get_the_ID() ); ?>"
                                               class="box">
                                        <label for="up-sell-check-id-<?php echo esc_attr( get_the_ID() ); ?>"></label>
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
                    <div class="button-row">
                        <a href="<?php echo get_permalink() . '?add-to-cart=' . implode( ',', $relatedIDs ); ?>"
                           class="lav-btn">
                            <button type="button" name="add-to-cart" class="single_add_to_cart_button button alt">
								<?php echo esc_html( $buttonText ); ?>
                            </button>
                        </a>
						<?php if ( $this->getValue( 'product_page_add_to_cart_desc' ) ): ?>
                            <span class="full-price-line">
                            <span class="price-desc">
                                <?php echo esc_html( $this->getValue( 'product_page_add_to_cart_desc' ) ); ?>
                            </span>
                            <span class="price-full"
                                  data-thousand="<?php echo esc_attr( wc_get_price_thousand_separator() ); ?>"
                                  data-decimal="<?php echo esc_attr( wc_get_price_decimal_separator() ); ?>"
                                  data-num="<?php echo esc_attr( wc_get_price_decimals() ); ?>">
                                    <?php echo sprintf( get_woocommerce_price_format(), "<span class='symbol-p'>" . get_woocommerce_currency_symbol() . "</span>", "<span class='value-p'>" . number_format( $fullPrice, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) . "</span>" ); ?>
                            </span>
                        </span>
						<?php endif; ?>
                    </div>
                </section>
			<?php endif; ?>
		<?php endif; ?>

		<?php
	}

	public function getFields(): array {
		return array(
			'name'   => 'product_page',
			'title'  => esc_html__( 'Related', 'up-sell-pro' ),
			'icon'   => 'fas fa-laptop',
			'fields' => array(
				array(
					'id'       => 'product_page_enable_related_products',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable related products', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable related products section on single product page', 'up-sell-pro' ),
					'default'  => '1',
					'help'     => esc_html__( 'It shows Up-sell\Cross-sell products for the current item and helps suggest to user-relevant products', 'up-sell-pro' ),
				),

				array(
					'id'         => 'product_page_relation_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Relation place', 'up-sell-pro' ),
					'options'    => array(
						'woocommerce_after_single_product'         => esc_html__( 'After product', 'up-sell-pro' ),
						'woocommerce_after_single_product_summary' => esc_html__( 'After summary', 'up-sell-pro' ),
					),
					'dependency' => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
					'default'    => 'woocommerce_after_single_product',
					'subtitle'   => esc_html__( 'Place to put related products section for product detail page', 'up-sell-pro' ),
				),

				array(
					'id'         => 'product_page_additional_products',
					'type'       => 'slider',
					'title'      => esc_html__( 'Quantity of Products', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up the max number of related products for the current item', 'up-sell-pro' ),
					'default'    => 2,
					'min'        => 1,
					'max'        => 3,
					'step'       => 1,
					'dependency' => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'product_page_add_bundle',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Customers often buy together with this product', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'product_page_add_to_cart',
					'type'        => 'text',
					'title'       => esc_html__( 'Button text', 'up-sell-pro' ),
					'default'     => esc_html__( 'Add to cart', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put text for button here', 'up-sell-pro' ),
					'dependency'  => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'product_page_add_to_cart_desc',
					'type'        => 'text',
					'title'       => esc_html__( 'Price description', 'up-sell-pro' ),
					'default'     => esc_html__( 'Full price: ', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put text here', 'up-sell-pro' ),
					'dependency'  => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'product_page_relation_priority',
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
					'dependency' => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'product_page_add_if_empty',
					'type'       => 'radio',
					'title'      => esc_html__( 'Add random relations', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Add products if relations are empty or don\'t match', 'up-sell-pro' ),
					'options'    => array(
						'yes' => 'Yes',
						'no'  => 'No',
					),
					'default'    => 'yes',
					'dependency' => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'product_page_relation_order',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Order by', 'up-sell-pro' ),
					'options'    => array(
						'rand'     => esc_html__( 'Random', 'up-sell-pro' ),
						'date'     => esc_html__( 'Date', 'up-sell-pro' ),
						'modified' => esc_html__( 'Modified', 'up-sell-pro' ),
					),
					'default'    => 'rand',
					'dependency' => array( 'product_page_enable_related_products', '==', '1', '', 'visible' ),
				),
			)
		);
	}
}
