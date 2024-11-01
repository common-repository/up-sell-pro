<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleCartRelated extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		$place = $this->getValue( 'cart_relation_place' )
			? $this->getValue( 'cart_relation_place' )
			: 'woocommerce_after_cart_contents';

		if ( $this->getValue( 'cart_enable_related_products' ) ) {
			add_action( $place, array( $this, 'render' ), 10 );
		}
	}

	public function getArgs() {
		return array(
			'posts_per_page' => $this->getValue( 'cart_additional_products' ) !== null
				? $this->getValue( 'cart_additional_products' )
				: 2,
			'orderby'        => $this->getValue( 'cart_relation_order' ) !== null
				? $this->getValue( 'cart_relation_order' )
				: 'rand',
			'add_random'     => $this->getValue( 'cart_add_if_empty' ) == 'yes',
			'type'           => $this->getValue( 'cart_relation_priority' ),
		);
	}

	public function render( $arguments = '' ) {
		global $woocommerce;
		$items           = $woocommerce->cart->get_cart();
		$cartProductsIds = [];

		if ( ! is_wp_error( $items ) ) {
			foreach ( $items as $item => $values ) {
				$_product = wc_get_product( $values['data']->get_id() );
				array_push( $cartProductsIds, $_product->get_id() );
			}
		}


		$args       = $this->getArgs();
		$provider   = new LavBoostDataLoader();
		$args['id'] = $cartProductsIds;
		$loop       = $provider->getData( $this->getValue( 'cart_relation_priority' ), $args );

		if ( $this->getValue( 'cart_add_if_empty' ) == 'yes' && ! $loop->have_posts() ) {
			$loop = $provider->getData( 'random', $args );
		}
		if ( !function_exists( 'woocommerce_template_loop_rating' ) && !function_exists( 'wc_get_product' ) ){
		    return;
        }

		?>
	    <?php if (!empty($loop) && $loop->have_posts() ): ?>
                <div class="section-products up-sell-products lav-boost-related lav-boost">
                    <div class="container">
						<?php if ( $this->getValue( 'cart_add_bundle' ) ): ?>
                            <h2 class="up-sell-products-title">
								<?php echo esc_html( $this->getValue( 'cart_add_bundle' ) ); ?>
                            </h2>
						<?php endif; ?>
                        <div class="list-products">
							<?php
							while ( $loop->have_posts() ) : $loop->the_post();
								global $post;
								$_product = wc_get_product( get_the_ID() );
								?>
                                <div class="product card related-product related-product-id-<?php echo esc_attr( get_the_ID() ); ?>">
									<?php
									if ( $_product->get_sale_price() ) {
										echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'up-sell-pro' ) . '</span>', $post, $_product );
									}
									?>
                                    <div class="top">
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
	                                    <?php woocommerce_template_loop_add_to_cart(); ?>
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
	}

	public function getFields(): array {
		return array(
			'name'   => 'cart_page',
			'title'  => esc_html__( 'Cart Page', 'up-sell-pro' ),
			'icon'   => 'fas fa-shopping-cart',
			'fields' => array(
				array(
					'id'       => 'cart_enable_related_products',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable related products', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable related products section on Cart page', 'up-sell-pro' ),
					'default'  => '1',
					'help'     => esc_html__( 'It shows Up-sell\Cross-sell products for added to Cart items and help suggest to user relevant products on Cart Page', 'up-sell-pro' ),
				),

				array(
					'id'         => 'cart_relation_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Relation place', 'up-sell-pro' ),
					'options'    => array(
						'woocommerce_after_cart_contents' => esc_html__( 'After content', 'up-sell-pro' ),
						'woocommerce_after_cart_table'    => esc_html__( 'After table', 'up-sell-pro' ),
						'woocommerce_after_cart'          => esc_html__( 'After cart', 'up-sell-pro' ),
					),
					'default'    => 'woocommerce_after_cart',
					'dependency' => array( 'cart_enable_related_products', '==', '1', '', 'visible' ),
					'subtitle'   => esc_html__( 'Place to put related products section for Cart page', 'up-sell-pro' ),
				),

				array(
					'id'         => 'cart_additional_products',
					'type'       => 'slider',
					'title'      => esc_html__( 'Quantity of Products', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up the max number of related products for on Cart page', 'up-sell-pro' ),
					'default'    => 2,
					'min'        => 1,
					'max'        => 5,
					'step'       => 1,
					'dependency' => array( 'cart_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'cart_add_bundle',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Buying together often', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'cart_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'cart_relation_priority',
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
					'dependency' => array( 'cart_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'cart_add_if_empty',
					'type'       => 'radio',
					'title'      => esc_html__( 'Add random relations', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Add products if relations are empty or didn\'t match', 'up-sell-pro' ),
					'options'    => array(
						'yes' => esc_html__( 'Yes', 'up-sell-pro' ),
						'no'  => esc_html__( 'No', 'up-sell-pro' ),
					),
					'default'    => 'yes',
					'dependency' => array( 'cart_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'cart_relation_order',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Order by', 'up-sell-pro' ),
					'options'    => array(
						'rand'     => esc_html__( 'Random', 'up-sell-pro' ),
						'date'     => esc_html__( 'Date', 'up-sell-pro' ),
						'modified' => esc_html__( 'Modified', 'up-sell-pro' ),
					),
					'default'    => 'rand',
					'dependency' => array( 'cart_enable_related_products', '==', '1', '', 'visible' ),
				),
			)
		);
	}
}
