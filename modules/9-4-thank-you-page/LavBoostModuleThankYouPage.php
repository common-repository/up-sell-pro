<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleThankYouPage extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'thank_enable_related_products' ) ) {
			add_action( 'woocommerce_thankyou', array( $this, 'render' ), + $this->getValue( 'thank_relation_place' ) );
		}
	}

	public function getArgs() {

		return array(
			'posts_per_page' => $this->getValue( 'thank_additional_products' ) !== null
				? $this->getValue( 'thank_additional_products' )
				: 2,
			'orderby'        => $this->getValue( 'thank_relation_order' ) !== null
				? $this->getValue( 'thank_relation_order' )
				: 'rand',
			'add_random'     => $this->getValue( 'thank_add_if_empty' ) == 'yes',
			'type'           => $this->getValue( 'thank_relation_priority' ),
		);
	}

	public function render( $order_id ) {
		$order           = wc_get_order( $order_id );
		$order_items      = $order->get_items();

		$orderProductsIds = [];

		if ( ! is_wp_error( $order_items ) ) {
			foreach ( $order_items as $item_id => $order_item ) {
				array_push( $orderProductsIds, $order_item->get_product_id() );
			}
		}

		$args       = $this->getArgs();
		$provider   = new LavBoostDataLoader();
		$args['id'] = $orderProductsIds;
		$loop       = $provider->getData( $this->getValue( 'thank_relation_priority' ), $args );

		if ( $this->getValue( 'thank_add_if_empty' ) == 'yes' && is_object($loop)  && ! $loop->have_posts() ) {
			$loop = $provider->getData( 'random', $args );
		}
		if ( !function_exists( 'woocommerce_template_loop_rating' ) && !function_exists( 'wc_get_product' ) ){
			return;
		}
		?>
		<?php if (is_object($loop) && $loop->have_posts() ): ?>
                <div class="lav-boost section-products up-sell-products lav-boost-related">
                    <div class="container">
						<?php if ( $this->getValue( 'thank_add_bundle' ) ): ?>
                            <h2 class="up-sell-products-title">
								<?php echo esc_html( $this->getValue( 'thank_add_bundle' ) ); ?>
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
			'name'   => 'thank_page',
			'title'  => esc_html__( 'Thank You Page', 'up-sell-pro' ),
			'icon'   => 'fas fa-pager',
			'fields' => array(
				array(
					'id'       => 'thank_enable_related_products',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable related products', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable related products section on Thank you page', 'up-sell-pro' ),
					'default'  => '1',
					'help'     => esc_html__( 'It shows Up-sell\Cross-sell products on Thank you page and help suggest to user relevant products', 'up-sell-pro' ),
				),

				array(
					'id'         => 'thank_relation_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Relation place', 'up-sell-pro' ),
					'options'    => array(
						'99' => esc_html__( 'Before table', 'up-sell-pro' ),
						'5'  => esc_html__( 'After table', 'up-sell-pro' ),
					),
					'default'    => '99',
					'dependency' => array( 'thank_enable_related_products', '==', '1', '', 'visible' ),
					'subtitle'   => esc_html__( 'Place to put related products section on Thank you page', 'up-sell-pro' ),
				),

				array(
					'id'         => 'thank_additional_products',
					'type'       => 'slider',
					'title'      => esc_html__( 'Quantity of Products', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up the number of related products for on Thank you page', 'up-sell-pro' ),
					'default'    => 2,
					'min'        => 1,
					'max'        => 4,
					'step'       => 1,
					'dependency' => array( 'thank_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'thank_add_bundle',
					'type'        => 'text',
					'title'       => esc_html__( 'Section title', 'up-sell-pro' ),
					'default'     => esc_html__( 'Buying together often', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
					'dependency'  => array( 'thank_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'thank_relation_priority',
					'type'       => 'select',
					'title'      => esc_html__( 'Relation data', 'up-sell-pro' ),
					'settings'    => array( 'width' => '50%' ),
					'options'    => array(
						'tags'       => esc_html__( 'Tags', 'up-sell-pro' ),
						'categories' => esc_html__( 'Categories', 'up-sell-pro' ),
						'viewed'     => esc_html__( 'Viewed', 'up-sell-pro' ),
					),
					'subtitle'   => esc_html__( 'Set up which data use for related products', 'up-sell-pro' ),
					'default'    => 'categories',
					'chosen'     => true,
					'dependency' => array( 'thank_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'thank_add_if_empty',
					'type'       => 'radio',
					'title'      => esc_html__( 'Add random relations', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Add products if relations are empty or didn\'t match', 'up-sell-pro' ),
					'options'    => array(
						'yes' => esc_html__( 'Yes', 'up-sell-pro' ),
						'no'  => esc_html__( 'No', 'up-sell-pro' ),
					),
					'default'    => 'yes',
					'dependency' => array( 'thank_enable_related_products', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'thank_relation_order',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Order by', 'up-sell-pro' ),
					'options' => array(
						'rand'     => esc_html__( 'Random', 'up-sell-pro' ),
						'date'     => esc_html__( 'Date', 'up-sell-pro' ),
						'modified' => esc_html__( 'Modified', 'up-sell-pro' ),
					),
					'default' => 'rand',
					'dependency' => array( 'thank_enable_related_products', '==', '1', '', 'visible' ),
				),
			)
		);
	}
}
