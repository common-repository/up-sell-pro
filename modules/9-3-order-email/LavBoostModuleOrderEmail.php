<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleOrderEmail extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();
		if ( ! empty( $this->getValue( 'email_add_to_order' ) ) && is_array( $this->getValue( 'email_add_to_order' ) ) ) {
			$this->render();
		}
	}

	public function getArgs() {
		return array(
			'posts_per_page' => $this->getValue( 'email_order_items' ) !== null
				? $this->getValue( 'email_order_items' )
				: 3,
			'orderby'        => $this->getValue( 'email_relation_order' ) !== null
				? $this->getValue( 'email_relation_order' )
				: 'rand',
			'offset_search'  => $this->getValue( 'general_keep_queries' ),
		);
	}

	public function getSearchQueriesEmailRow( $value, $data = null ) {
		$markup = [
			'title'   => '<div class="email-queries-title"><strong>' . esc_html__( 'Search queries: ', 'up-sell-pro' ) . '<strong></div>',
			'content' => '',
		];

		if ( is_array( $data ) ) {
			$content = '';
			foreach ( $data as $key => $value ) {
				$content .= '<strong>' . esc_html( $value ) . '</strong>' . $this->getSeparator( $key, count( $data ) );
			}
			$markup['content'] .= '<div class="email-queries-content">' . $content . '</div>';
		}

		return $markup;
	}

	public function getSeparator( $key, $length ) {
		return $key + 1 == $length ? '' : ', ';
	}

	public function getTabTitle( $value ) {
		$tabs = [
			'tags'       => [ 'title' => esc_html__( 'Related by Tags', 'up-sell-pro' ), 'id' => $value ],
			'categories' => [ 'title' => esc_html__( 'Related by Categories', 'up-sell-pro' ), 'id' => $value ],
			'viewed'     => [ 'title' => esc_html__( 'Viewed products', 'up-sell-pro' ), 'id' => $value ],
			'search'     => [ 'title' => esc_html__( 'Search queries', 'up-sell-pro' ), 'id' => $value ],
		];

		return $tabs[ $value ];
	}

	public function getEmailRowContent( $value, $data = null, $provider = null ) {
		$tabTitle = $this->getTabTitle( $value );
		$loop     = $provider->getData( $value, $data );

		$markup = [
			'title'   => '<div class="email-content-title"><strong>' . esc_html( $tabTitle['title'] ) . '<strong></div>',
			'content' => '',
		];


		$content = '';

		if ( is_object($loop) && property_exists($loop, 'posts') && is_array($loop->posts)  && count($loop->posts) ) {
			foreach ( $loop->posts as $key => $post ) {
				$product = wc_get_product( $post->ID );
				$content .= '<div>  
                           <a href="' . esc_url( get_permalink( $post->ID ) ) . '">
			                  <span class="drop__name">' . $post->post_title . '</span> - 
			                  <span class="card-text">' . esc_html__( 'price: ', 'up-sell-pro' ) . $product->get_price_html() . '</span>
			                </a>
			             <div>';
			}
		} else {
			$content .= esc_html__( 'Nothing to show', 'up-sell-pro' );
		}

		wp_reset_query();

		$markup['content'] .= $content;

		return $markup;
	}

	public function renderRows( $order, $sent_to_admin, $plain_text, $email ) {

		if ( $sent_to_admin && ! $plain_text ) {
			$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
			$provider = new LavBoostDataLoader();

			$order            = wc_get_order( $order_id );
			$order_items      = $order->get_items();
			$orderProductsIds = [];

			if ( ! is_wp_error( $order_items ) ) {
				foreach ( $order_items as $item_id => $order_item ) {
					array_push( $orderProductsIds, $order_item->get_product_id() );
				}
			}

			$args       = $this->getArgs();
			$args['id'] = $orderProductsIds;

			$output = '<h2>' . esc_html__( 'LavBoost Info', 'up-sell-pro' ) . '</h2>';
			foreach ( $this->getValue( 'email_add_to_order' ) as $key => $value ) {
				// render search queries
				if ( $value == 'search' ) {
					$row    = $this->getSearchQueriesEmailRow( $value, unserialize( get_post_meta( $order_id, '_search_lav_queries', true ) ) );
					$output .= $row['title'];
					$output .= $row['content'];
				}
				// render viewed products
				if ( $value == 'viewed' ) {
					$row    = $this->getEmailRowContent( $value, $args, $provider );
					$output .= $row['title'];
					$output .= $row['content'];
				}
				// render related by categories products
				if ( $value == 'categories' ) {
					$row    = $this->getEmailRowContent( $value, $args, $provider );
					$output .= $row['title'];
					$output .= ! empty( $row['content'] ) ? $row['content'] : esc_html__( 'Nothing to show', 'up-sell-pro' );
				}
				// render related by tags products
				if ( $value == 'tags' ) {
					$row    = $this->getEmailRowContent( $value, $args, $provider );
					$output .= $row['title'];
					$output .= ! empty( $row['content'] ) ? $row['content'] : esc_html__( 'Nothing to show', 'up-sell-pro' );
				}
			}
			echo wp_kses( $output, $this->getAllowedTags() );
		}


	}

	public function render( $arguments = '' ) {
		add_action( 'woocommerce_email_customer_details', array( $this, 'renderRows' ), 90, 4 );
	}


	public function getFields(): array {
		return array(
			'name'   => 'email',
			'title'  => esc_html__( 'Email', 'up-sell-pro' ),
			'icon'   => 'far fa-envelope',
			'fields' => array(
				array(
					'id'       => 'email_add_to_order',
					'type'     => 'checkbox',
					'title'    => esc_html__( 'Add to order Email', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Chose which data type need to add on Order Email', 'up-sell-pro' ),
					'help'     => esc_html__( 'It shows Up-sell\Cross-sell recommendation and search query in Order Email to help you suggest smartly additional products  if you call to customer to confirm the order', 'up-sell-pro' ),
					'options'  => array(
						'search'     => esc_html__( 'Search queries', 'up-sell-pro' ),
						'viewed'     => esc_html__( 'Viewed products', 'up-sell-pro' ),
						'categories' => esc_html__( 'Category relations', 'up-sell-pro' ),
						'tags'       => esc_html__( 'Tag relations', 'up-sell-pro' ),
					),
					'default'  => array(
						'search',
						'viewed',
						'categories',
						'tags',
					),
				),

				array(
					'id'       => 'email_order_items',
					'type'     => 'slider',
					'title'    => esc_html__( 'Quantity of items', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Set up the max number of related products for Order Email for every data type', 'up-sell-pro' ),
					'default'  => 5,
					'min'      => 1,
					'max'      => 15,
					'step'     => 1,
				),

				array(
					'id'      => 'email_relation_order',
					'type'    => 'button_set',
					'title'   => esc_html__( 'Order by', 'up-sell-pro' ),
					'options' => array(
						'rand'     => esc_html__( 'Random', 'up-sell-pro' ),
						'date'     => esc_html__( 'Date', 'up-sell-pro' ),
						'modified' => esc_html__( 'Modified', 'up-sell-pro' ),
					),
					'default' => 'rand',
				),
			)
		);
	}
}
