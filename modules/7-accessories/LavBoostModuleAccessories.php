<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleAccessories extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		$place = $this->getValue( 'accessories_relation_place' )
			? $this->getValue( 'accessories_relation_place' )
			: 'woocommerce_after_single_product_summary';

		if ( $this->getValue( 'accessories_enable_related_products' ) ) {
			add_action( $place, array( $this, 'render' ), 10 );
		}
	}

	public function getArgs() {
		return array(
			'posts_per_page' => $this->getValue( 'accessories_additional_products' ) !== null
			? $this->getValue( 'accessories_additional_products' )
			: 10,
			'orderby'        => $this->getValue( 'accessories_relation_order' ) !== null
				? $this->getValue( 'accessories_relation_order' )
				: 'rand',
			'add_random'     => false,
			'type'           => $this->getValue( 'accessories_relation_priority' ),
		);
	}

	public function render( $arguments = '' ) {
		global $product, $post;

		if ( $product->is_visible() && ! $product->is_in_stock() ) {
			return;
		}

		$args          = $this->getArgs();
		$provider      = new LavBoostDataLoader();
		$args['id']    = $product->get_id();
		$loop          = $provider->getData( $this->getValue( 'accessories_relation_priority' ), $args );
		$relatedIDs = [ $product->get_id() ];

		if ( $this->getValue( 'accessories_add_if_empty' ) == 'yes' && ! $loop->have_posts() ) {
			$loop = $provider->getData( 'random', $args );
		}

		$buttonText = !empty($this->getValue( 'accessories_add_to_cart' ))
			? $this->getValue( 'accessories_add_to_cart' )
			: esc_html__('Add to cart','up-sell-pro')
		?>
		<?php if ( !empty($loop) && $loop->have_posts() ): ?>
            <section class="lav-boost section-products accessories-products">
	                <?php if ( $this->getValue( 'accessories_add_bundle' ) ): ?>
                        <h2 class="up-sell-products-title">
			                <?php echo esc_html( $this->getValue( 'accessories_add_bundle' ) ); ?>
                        </h2>
	                <?php endif; ?>
                    <div class="accessories-slider">
                        <div class="swiper-wrapper">
	                        <?php
	                        while ( $loop->have_posts() ) : $loop->the_post();
		                        global $post;
		                        $_product  = wc_get_product( get_the_ID() );
		                        ?>
                                <!-- Slides -->
                                <div class="swiper-slide">
                                    <div class="product swiper-slide card related-product related-product-id-<?php echo esc_attr( get_the_ID() ); ?>  disabled"  data-price="<?php echo $_product->get_price(); ?>">
                                        <div class="top">
			                                <?php
			                                if ( $_product->get_sale_price() ) {
				                                echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'up-sell-pro' ) . '</span>', $post, $_product );
			                                }
			                                ?>
                                            <a href="<?php echo esc_url( get_permalink(get_the_ID()) ); ?>">
				                                <?php echo $_product->get_image( 'medium' ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            </a>
                                        </div>
                                        <div class="bottom">
                                            <input id="accessories-up-sell-check-id-<?php echo esc_attr( get_the_ID() ); ?>" type="checkbox"
                                                   data-id="<?php echo esc_attr( get_the_ID() ); ?>"
                                                   class="box">
                                            <label for="accessories-up-sell-check-id-<?php echo esc_attr( get_the_ID() ); ?>"></label>
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
                                </div>
	                        <?php
	                        endwhile;
	                        wp_reset_query();
	                        ?>
                        </div>
                        <!-- If we need pagination -->
                        <div class="swiper-pagination"></div>
                        <!-- If we need navigation buttons -->
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>
                    <div class="button-row">
                        <a href="<?php echo get_permalink() . '?add-to-cart=' . implode( ',', $relatedIDs ); ?>" class="lav-btn">
                            <button disabled type="button" name="add-to-cart" class="single_add_to_cart_button button alt">
                                <?php echo esc_html( $buttonText ); ?>
                            </button>
                        </a>
                    </div>
            </section>
		<?php endif; ?>

		<?php
	}



	public function getFields(): array {
		return array(
			'name'   => 'accessories',
			'title'  => esc_html__( 'Accessories', 'up-sell-pro' ),
			'icon'   => 'fab fa-usb',
			'fields' => array(
				array(
					'id'       => 'accessories_enable_related_products',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable Accessories', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable Accessories products section on the single product page', 'up-sell-pro' ),
					'default'  => '1',
					'help'     => esc_html__( 'It shows accessories products for current item and help suggest to user relevant products', 'up-sell-pro' ),
				),

				array(
					'id'         => 'accessories_relation_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Accessories place', 'up-sell-pro' ),
					'options'    => array(
						'woocommerce_after_single_product'         => esc_html__( 'After product', 'up-sell-pro' ),
						'woocommerce_after_single_product_summary' => esc_html__( 'After summary', 'up-sell-pro' ),
					),
					'dependency' => array( 'accessories_enable_related_products', '==', '1', '', 'visible' ),
					'default'    => 'woocommerce_after_single_product',
					'subtitle'   => esc_html__( 'Place to put accessories products section for product detail page', 'up-sell-pro' ),
				),


				// A Notice
				array(
					'type'    => 'notice',
					'style'   => 'info',
					'content' => esc_html__('You can add up to 6 accessories if you need more accessories so please buy paid version - ', 'up-sell-pro') . '<a href="'. esc_url('https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/')  .'" target="_blank">'. esc_html__('LavBoost', 'up-sell-pro').'</a>',
				),

				array(
					'id'         => 'accessories_additional_products',
					'type'       => 'slider',
					'title'      => esc_html__( 'Quantity of Products', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up the number of Accessories for current item', 'up-sell-pro' ),
					'default'    => 5,
					'min'        => 3,
					'max'        => 6,
					'step'       => 1,
					'dependency' => array( 'accessories_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'accessories_add_bundle',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Customers often buy together with this product', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'accessories_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'accessories_add_to_cart',
					'type'        => 'text',
					'title'       => esc_html__( 'Button text', 'up-sell-pro' ),
					'default'     => esc_html__( 'Add to cart', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put text for button here', 'up-sell-pro' ),
					'dependency'  => array( 'accessories_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'accessories_relation_priority',
					'type'       => 'select',
					'title'      => esc_html__( 'Accessories data', 'up-sell-pro' ),
					'options'    => array(
						'tags'       => esc_html__( 'Tags', 'up-sell-pro' ),
						'categories' => esc_html__( 'Categories', 'up-sell-pro' ),
						'viewed'     => esc_html__( 'Viewed', 'up-sell-pro' ),
					),
					'subtitle'   => esc_html__( 'Set up which data use for related products', 'up-sell-pro' ),
					'default'    => 'categories',
					'settings'    => array( 'width' => '50%' ),
					'chosen'     => true,
					'dependency' => array( 'accessories_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'accessories_relation_order',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Order by', 'up-sell-pro' ),
					'options'    => array(
						'rand'     => esc_html__( 'Random', 'up-sell-pro' ),
						'date'     => esc_html__( 'Date', 'up-sell-pro' ),
						'modified' => esc_html__( 'Modified', 'up-sell-pro' ),
					),
					'default'    => 'rand',
					'dependency' => array( 'accessories_enable_related_products', '==', '1', '', 'visible' ),
				),
			)
		);
	}
}
