<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use traits\TLavBoostSingleton;

class LavBoostModuleQuantityDiscount extends LavBoostModule {
	use TLavBoostSingleton;

	public function run( $args = '' ) {
		$this->createSettingsTab();

		$place = $this->getValue( 'quantity_discount_place' )
			? $this->getValue( 'quantity_discount_place' )
			: 'woocommerce_product_meta_end';

		if ( $this->getValue( 'quantity_discount_enable' ) ) {
			add_action( $place, array( $this, 'render' ), 10 );
		}
		if ( $this->getValue( 'quantity_discount_enable' ) ) {
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'applyQuantityRangeDiscount' ) );
		}
	}

	public function applyQuantityRangeDiscount() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		if ( $this->getValue( 'quantity_discount_responsibility' ) == 'all' ) {
			$this->applyDiscountForAll( $this->getValue( 'discount_conditions_if_all' ) );
		} else {
			$this->applyCustomDiscounts();
		}
	}

	public function getDiscountRule( $discount_rules, $discount_type ) {
		$discount_rule = null;
		if ( empty( $discount_rules ) || ! is_array( $discount_rules ) || empty( $discount_type ) ) {
			return null;
		}
		// Find the discount rule based on the discount type
		foreach ( $discount_rules as $rule ) {
			if ( $rule['discount_type'] == $discount_type ) {
				$discount_rule = $rule;
				break;
			}
		}

		return $discount_rule;
	}


	public function hasDiscount( $product_id, $discount_type, $discount_rule ) {
		// Check if the discount rule applies to the current cart item
		$apply_discount = false;

		if ( $discount_type === 'product' ) {
			// Check if the product ID matches
			$apply_discount = ( $product_id == $discount_rule['discount_product'] );
		} elseif ( $discount_type === 'category' ) {
			// Check if the product belongs to any of the specified categories
			$categories     = $discount_rule['discount_categories'];
			$apply_discount = has_term( $categories, 'product_cat', $product_id );
		} elseif ( $discount_type === 'tag' ) {
			// Check if the product has any of the specified tags
			$tags           = $discount_rule['discount_tags'];
			$apply_discount = has_term( $tags, 'product_tag', $product_id );
		}

		return $apply_discount;
	}

	public function getDiscountValue( $price, $discountRange, $quantity ) {

		if ( empty( $price ) || empty( $discountRange ) ) {
			return 0;
		}

		if ( empty( $discountRange['discount_value'] ) || empty( $discountRange['discount_type'] ) ) {
			return 0;
		}

		if ( $discountRange['discount_type'] != 'percent' ) {
			if ( $discountRange['discount_value'] >= ( $price * $quantity ) ) {
				return $price * $quantity;
			} else {
				return $discountRange['discount_value'];
			}
		}

		if ( $discountRange['discount_value'] >= 100 ) {
			return $price * $quantity;
		}

		if ( $discountRange['discount_value'] > 0 ) {
			return ( ( $price ) / 100 * $discountRange['discount_value'] ) * $quantity;
		}
	}


	public function applyDiscountForAll( $quantity_ranges ) {
		if ( empty( $quantity_ranges ) || ! is_array( $quantity_ranges ) ) {
			return;
		}
		usort( $quantity_ranges, function ( $a, $b ) {
			return intval( $a['min_qty'] ) - intval( $b['min_qty'] );
		} );

		$cart         = WC()->cart;
		$allDiscounts = 0;
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$quantity       = $cart_item['quantity'];
			$discount_range = null;
			foreach ( $quantity_ranges as $range ) {
				if ( ! empty( $range['min_qty'] ) && $quantity >= $range['min_qty'] ) {
					$discount_range = $range;
				} else {
					break;
				}
			}
			if ( $discount_range ) {
				$allDiscounts += $this->getDiscountValue( $cart_item['data']->get_price(), $discount_range, $quantity );
			}
		}

		if ( $allDiscounts > 0 ) {
			$cart->add_fee( __( 'Quantity Range Discount', 'up-sell-pro' ), - $allDiscounts );
		}
	}

	public function getCustomDiscounts( $product_id ) {

		$discount_priority = $this->getValue( 'quantity_discount_priority' )['enabled'];

		$discount_rules = $this->getValue( 'quantity_discount_items' );

		$conditions = null;
		if ( empty( $product_id ) || empty( $discount_rules ) || empty( $discount_priority ) ) {
			return null;
		}

		if ( ! is_array( $discount_priority ) ) {
			return null;
		}

		foreach ( $discount_priority as $discount_type => $discount_name ) {
			// Find the discount rule based on the discount type
			$discount_rule = $this->getDiscountRule( $discount_rules, $discount_type );
			if ( $discount_rule ) {
				// Check if the discount rule applies to the current cart item
				$apply_discount = $this->hasDiscount( $product_id, $discount_type, $discount_rule );
				if ( $apply_discount ) {
					// Apply the discount if the conditions are met
					$conditions = $discount_rule['discount_conditions_not_all'];
					break;
				}
			}
		}

		return $conditions;
	}


	public function applyCustomDiscounts() {
		$cart              = WC()->cart;
		$discount_priority = $this->getValue( 'quantity_discount_priority' )['enabled'];

		$discount_rules = $this->getValue( 'quantity_discount_items' );
		$allDiscounts   = 0;

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			// Get the product quantity
			$quantity = $cart_item['quantity'];

			// Get the product ID
			$product_id = $cart_item['product_id'];

			// Apply discounts based on priority
			foreach ( $discount_priority as $discount_type => $discount_name ) {
				// Find the discount rule based on the discount type
				$discount_rule = $this->getDiscountRule( $discount_rules, $discount_type );
				if ( $discount_rule ) {
					// Check if the discount rule applies to the current cart item
					$apply_discount = $this->hasDiscount( $product_id, $discount_type, $discount_rule );
					if ( $apply_discount ) {
						// Apply the discount if the conditions are met
						$conditions     = $discount_rule['discount_conditions_not_all'];
						$discount_range = null;
						usort( $conditions, function ( $a, $b ) {
							return intval( $a['min_qty'] ) - intval( $b['min_qty'] );
						} );
						foreach ( $conditions as $condition ) {
							if ( ! empty( $condition['min_qty'] ) && $quantity >= $condition['min_qty'] ) {
								$discount_range = $condition;
							} else {
								break;
							}
						}
						if ( $discount_range ) {
							$allDiscounts += $this->getDiscountValue( $cart_item['data']->get_price(), $discount_range, $quantity );
						}
						break;
					}
				}
			}
		}
		if ( $allDiscounts > 0 ) {
			$cart->add_fee( __( 'Quantity Range Discount', 'up-sell-pro' ), - $allDiscounts );
		}
	}


	public function getDiscountString( $type, $value ) {

		if ( $type == 'fixed' ) {
			return sprintf( get_woocommerce_price_format(), "<strong><span class='symbol-p'>" . get_woocommerce_currency_symbol() . "</span>", "<span class='value-p'>" . number_format( $value, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) . "</span></strong>" );
		} else {
			return '<strong><span class="value">' . $value . '</span><span class="symbol">%</span></strong>';
		}

	}

	public function getAllDiscountTable( $conditions ) {
		$output = '';
		if ( empty( $conditions ) || ! is_array( $conditions ) ) {
			return $output;
		}

		foreach ( $conditions as $condition ) {
			if ( empty( $condition['min_qty'] ) || empty( $condition['discount_value'] ) ) {
				continue;
			}
			$output .= '<div class="discount-row"><strong><span class="qty">' . esc_html( $condition['min_qty'] ) . '</span></strong> ' . esc_html__( 'and more: ', 'up-sell-pro' ) . $this->getDiscountString( $condition['discount_type'], $condition['discount_value'] ) . '</div>';
		}

		return $output;
	}

	public function render( $arguments = '' ) {
		?>
		<?php if ( $this->getValue( 'quantity_discount_enable' ) ): ?>
            <div class="lav-boost lav-quantity-discount">
				<?php if ( $this->getValue( 'quantity_discount_title' ) ): ?>
                    <h5 class="discount-title">
						<?php echo esc_html( $this->getValue( 'quantity_discount_title' ) ); ?>
                    </h5>
				<?php endif; ?>
                <div class="discount-body">
                <?php if ( $this->getValue( 'quantity_discount_responsibility' ) == 'all' ): ?>
                    <?php echo wp_kses_post( $this->getAllDiscountTable( $this->getValue( 'discount_conditions_if_all' ) ) ); ?>
                <?php else: ?>
                    <?php
                    global $product;
                    $id         = $product->get_id();
                    $conditions = $this->getCustomDiscounts( $id );
                    ?>
                    <?php echo wp_kses_post( $this->getAllDiscountTable( $conditions ) ); ?>
                <?php endif; ?>
                </div>
            </div>
		<?php endif; ?>

		<?php

	}

	public function getFields(): array {
		return array(
			'name'   => 'quantity_discount',
			'title'  => esc_html__( 'Quantity Discount', 'up-sell-pro' ),
			'icon'   => 'fas fa-percent',
			'fields' => array(
				array(
					'id'       => 'quantity_discount_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable Quantity Discount', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable Quantity Discount', 'up-sell-pro' ),
					'default'  => '0',
				),

				array(
					'id'         => 'quantity_discount_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Quantity Discount place', 'up-sell-pro' ),
					'options'    => array(
						'woocommerce_product_meta_end'        => esc_html__( 'After meta', 'up-sell-pro' ),
						'woocommerce_before_add_to_cart_form' => esc_html__( 'After price', 'up-sell-pro' ),
					),
					'dependency' => array( 'quantity_discount_enable', '==', '1', '', 'visible' ),
					'default'    => 'woocommerce_product_meta_end',
					'subtitle'   => esc_html__( 'The place to put the discount table on a single product page', 'up-sell-pro' ),
				),


				array(
					'id'         => 'quantity_discount_title',
					'type'       => 'text',
					'title'      => esc_html__( 'Title', 'up-sell-pro' ),
					'default'    => esc_html__( 'Quantity Discount', 'up-sell-pro' ),
					'dependency' => array( 'quantity_discount_enable', '==', '1', '', 'visible' ),
					'subtitle'   => esc_html__( 'Title for the discount table on a single product page', 'up-sell-pro' ),
				),

				array(
					'id'         => 'quantity_discount_responsibility',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Discount rule', 'up-sell-pro' ),
					'options'    => array(
						'all'    => esc_html__( 'All products', 'up-sell-pro' ),
						'custom' => esc_html__( 'Custom', 'up-sell-pro' ),
					),
					'dependency' => array( 'quantity_discount_enable', '==', '1', '', 'visible' ),
					'default'    => 'all',
					'subtitle'   => esc_html__( 'Rules to apply discounts', 'up-sell-pro' ),
				),
				///////////////////////// All Products Discounts ///////////////////////////////////////
				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'You can add up to 3 conditions if you need more conditions so please buy paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency'   => array(
						'quantity_discount_enable|quantity_discount_responsibility',
						'==|==',
						'1|all'
					),
				),

                array(
					'type'         => 'repeater',
					'id'           => 'discount_conditions_if_all',
					'title'        => esc_html__( 'Conditions', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Set up conditions', 'up-sell-pro' ),
					'max'          => 3,
					'button_title' => esc_html__( 'Add discount condition', 'up-sell-pro' ),
					'dependency'   => array(
						'quantity_discount_enable|quantity_discount_responsibility',
						'==|==',
						'1|all'
					),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),

						array(
							'id'      => 'min_qty',
							'type'    => 'number',
							'title'   => esc_html__( 'Min Qty', 'up-sell-pro' ),
							'default' => 1,
							'step'    => 1,
							'min'     => 0,
						),

						array(
							'id'      => 'discount_type',
							'type'    => 'button_set',
							'title'   => esc_html__( 'Discount type', 'up-sell-pro' ),
							'options' => array(
								'fixed'   => esc_html__( 'Fixed', 'up-sell-pro' ),
								'percent' => esc_html__( 'Percent', 'up-sell-pro' ),
							),
							'default' => 'percent',
						),

						array(
							'id'      => 'discount_value',
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
				),
				///////////////////////// Custom Discounts ///////////////////////////////////////
				array(
					'id'            => 'quantity_discount_priority',
					'type'          => 'sorter',
					'title'         => esc_html__( 'Discount priority', 'up-sell-pro' ),
					'subtitle'      => esc_html__( 'Discount priority to set up with condition has priority', 'up-sell-pro' ),
					'enabled_title' => 'Priority(from top to bottom)',
					'disabled'      => false,
					'dependency'    => array(
						'quantity_discount_enable|quantity_discount_responsibility',
						'==|==',
						'1|custom'
					),
					'default'       => array(
						'enabled' => array(
							'product'  => esc_html__( 'Product', 'up-sell-pro' ),
							'category' => esc_html__( 'Category', 'up-sell-pro' ),
							'tag'      => esc_html__( 'Tag', 'up-sell-pro' ),
						),
					),
				),

				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'You can add up to 3 conditions if you need more conditions so please buy paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency'   => array(
						'quantity_discount_enable|quantity_discount_responsibility',
						'==|==',
						'1|custom'
					),
				),

				array(
					'id'           => 'quantity_discount_items',
					'type'         => 'repeater',
					'dependency'   => array(
						'quantity_discount_enable|quantity_discount_responsibility',
						'==|==',
						'1|custom'
					),
					'max'          => 3,
					'title'        => esc_html__( 'Discounts', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Discounts rules', 'up-sell-pro' ),
					'button_title' => esc_html__( 'Add Discount', 'up-sell-pro' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'discount_type',
							'type'        => 'select',
							'width'       => '250px',
							'title'       => esc_html__( 'Discount type', 'up-sell-pro' ),
							'chosen'      => true,
							'placeholder' => esc_html__( 'Select type', 'up-sell-pro' ),
							'options'     => array(
								'product'  => esc_html__( 'Product', 'up-sell-pro' ),
								'category' => esc_html__( 'Category', 'up-sell-pro' ),
								'tag'      => esc_html__( 'Tag', 'up-sell-pro' ),
							),
							'default'     => array( 'product' ),
						),
						array(
							'id'          => 'discount_product',
							'type'        => 'select',
							'title'       => esc_html__( 'Product', 'up-sell-pro' ),
							'subtitle'    => esc_html__( 'Choose product', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'placeholder' => esc_html__( 'Select product', 'up-sell-pro' ),
							'options'     => 'posts',
							'query_args'  => array(
								'post_type'      => 'product',
								'status'         => 'publish',
								'posts_per_page' => - 1,
							),
							'dependency'  => array( 'discount_type', '==', 'product' ),
						),

						array(
							'id'          => 'discount_tags',
							'type'        => 'select',
							'width'       => '250px',
							'title'       => esc_html__( 'Tags', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Select tags', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'sortable'    => true,
							'options'     => 'tags',
							'query_args'  => array(
								'taxonomy' => 'product_tag',
							),
							'dependency'  => array( 'discount_type', '==', 'tag' ),
						),

						array(
							'id'          => 'discount_categories',
							'type'        => 'select',
							'title'       => esc_html__( 'Categories', 'up-sell-pro' ),
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
							'dependency'  => array( 'discount_type', '==', 'category' ),
						),

						array(
							'type'         => 'repeater',
							'id'           => 'discount_conditions_not_all',
							'title'        => esc_html__( 'Conditions', 'up-sell-pro' ),
							'subtitle'     => esc_html__( 'Set up conditions', 'up-sell-pro' ),
							'max'          => 5,
							'button_title' => esc_html__( 'Add discount condition', 'up-sell-pro' ),
							'dependency'   => array( 'discount_type', '!=', '', '', 'visible' ),
							'fields'       => array(
								array(
									'type'    => 'submessage',
									'style'   => 'success',
									'content' => '',
								),
								array(
									'id'      => 'min_qty',
									'type'    => 'number',
									'title'   => esc_html__( 'Min Qty', 'up-sell-pro' ),
									'default' => 1,
									'step'    => 1,
									'min'     => 0,
								),

								array(
									'id'      => 'discount_type',
									'type'    => 'button_set',
									'title'   => esc_html__( 'Discount type', 'up-sell-pro' ),
									'options' => array(
										'fixed'   => esc_html__( 'Fixed', 'up-sell-pro' ),
										'percent' => esc_html__( 'Percent', 'up-sell-pro' ),
									),
									'default' => 'percent',
								),

								array(
									'id'      => 'discount_value',
									'type'    => 'number',
									'title'   => esc_html__( 'Discount value', 'up-sell-pro' ),
									'default' => 10,
									'step'    => 0.1,
									'min'     => 0.01,
								),
								array(
									'type'    => 'submessage',
									'style'   => 'success',
									'content' => '',
								),
							),
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
