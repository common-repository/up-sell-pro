<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleOrderRecommendation extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();
		if ( ! empty( $this->getValue( 'order_add_to_order' ) ) && is_array( $this->getValue( 'order_add_to_order' ) ) ) {
			foreach ( $this->getValue( 'order_add_to_order' ) as $key => $value ) {
				if ( $value == 'search' ) {
					add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'saveSearchQueries' ) );
				}
			}
			add_action( 'add_meta_boxes', array( $this, 'render' ) );
		}
	}

	public function getArgs() {
		return array(
			'posts_per_page' => $this->getValue( 'order_order_items' ) !== null
				? $this->getValue( 'order_order_items' )
				: 3,
			'orderby'        => $this->getValue( 'order_relation_order' ) !== null
				? $this->getValue( 'order_relation_order' )
				: 'rand',
			'offset_search'  => $this->getValue( 'general_keep_queries' ),
		);
	}

	public function saveSearchQueries( $order_id ) {
		$provider =  new LavBoostDataLoader();
		update_post_meta( $order_id, '_search_lav_queries', serialize( $provider->getData('search', $this->getArgs() ) ) );
	}

	public function renderTabs() {
		global $post;

		$provider =  new LavBoostDataLoader();
		$order            = wc_get_order( $post->ID );
		$order_items      = $order->get_items();
		$orderProductsIds = [];

		if ( ! is_wp_error( $order_items ) ) {
			foreach ( $order_items as $item_id => $order_item ) {
				array_push( $orderProductsIds, $order_item->get_product_id() );
			}
		}

		$args       = $this->getArgs();
		$args['id'] = $orderProductsIds;

		$tabs        = '';
		$tabsContent = '';

		foreach ( $this->getValue('order_add_to_order') as $key => $value ) {
			// render viewed products
			if ( $value == 'viewed' ) {
				$tab         = $this->getTabContent( $value, $args, $provider);
				$tabs        .= $tab['tab'];
				$tabsContent .= $tab['content'];
			}
			// render search queries
			if ( $value == 'search' ) {
				$tab         = $this->getSearchQueriesTab( $value, unserialize( get_post_meta( $post->ID, '_search_lav_queries', true ) ) );
				$tabs        .= $tab['tab'];
				$tabsContent .= $tab['content'];
			}
			// render related by categories products
			if ( $value == 'categories' ) {
				$tab         = $this->getTabContent( $value, $args, $provider );
				$tabs        .= $tab['tab'];
				$tabsContent .= $tab['content'];
			}
			// render related by tags products
			if ( $value == 'tags' ) {
				$tab         = $this->getTabContent( $value, $args, $provider );
				$tabs        .= $tab['tab'];
				$tabsContent .= $tab['content'];
			}
		}
		?>
        <div class="up-sell-pro-tabs">
            <ul class="tabs__button-group">
                <?php echo wp_kses( $tabs, $this->getAllowedTags()); ?>
            </ul>
            <ul class="tabs__container">
                <?php echo wp_kses( $tabsContent, $this->getAllowedTags()) ; ?>
            </ul>
        </div>
		<?php
	}

	public function getTabContent( $value, $data = null, $provider = null ) {
		$tabTitle = $this->getTabTitle( $value );
		$loop     = $provider->getData( $value, $data );
		$markup   = [
			'tab'     => '<li><div class="tabs__toggle" data-tab=' . esc_attr( $value ) . '>' . esc_html( $tabTitle['title'] ) . '</div></li>',
			'content' => '',
		];
		$content = '';
		if(is_object($loop) && property_exists($loop, 'posts') && is_array($loop->posts)  && count($loop->posts)){
			foreach ( $loop->posts as $key => $post ) {
				$product = wc_get_product( $post->ID );
				$content .= '<div class="drop__card">
			                <div class="drop__data">
			                    ' . $product->get_image( "thumbnail" ) . '
			                    <div>
			                        <a class="item-link" href="' . esc_url(get_permalink( $post->ID ))  . '" target="_blank">' . $post->post_title . '</a>
			                    </div>
			                </div>
			                <div>
			                    <p class="card-text"><span  class="price-text">'. esc_html__('Price: ','up-sell-pro'). '</span>' . $product->get_price_html() . '</p>
			                </div>
			            </div>';
			}
		} else{
			$content .= esc_html__('Nothing to show','up-sell-pro');
		}

		wp_reset_query();

		$markup['content'] .= '<li class="tabs__tab-panel" data-tab="' . esc_attr( $value ) . '">
								<div class="tabs__content">
								  <div class="drop">
                                     <div class="drop__container" id="drop-items">'
		                      . $content .
		                      '</div>
                                  </div>
                                </div>
							   </li>';

		return $markup;
	}

	public function getTabTitle( $value ) {
		$tabs = [
			'tags'      => [ 'title' =>  esc_html__( 'Related by Tags', 'up-sell-pro' ), 'id' => $value ],
			'categories' => [ 'title' => esc_html__( 'Related by Categories', 'up-sell-pro' ), 'id' => $value ],
			'viewed'   => [ 'title' => esc_html__( 'Viewed products', 'up-sell-pro' ), 'id' => $value ],
			'search'   => [ 'title' => esc_html__( 'Search queries', 'up-sell-pro' ), 'id' => $value ],
		];

		return $tabs[ $value ];
	}

	public function getSearchQueriesTab( $value, $data = null ) {
		$markup = [
			'tab'     => '<li><div class="tabs__toggle" data-tab=' . esc_attr( $value ) . '>' . esc_html__( 'Search queries', 'up-sell-pro' ) . '</div></li>',
			'content' => '',
		];


		$content = '';
		if (  is_array($data) && count($data) ){
			foreach ( $data as $key => $value ) {
				$content .= '<strong>' . esc_html( $value ) . '</strong>' . $this->getSeparator( $key, count( $data ) );
			}
		} else {
			$content .= esc_html__('Nothing to show','up-sell-pro');
		}

		$markup['content'] .= '<li class="tabs__tab-panel" data-tab="search">
                                    <div class="tabs__content">
                                        <p>'
		                      . $content .
		                      '</p>
                                    </div>
                                </li>';

		return $markup;
	}

	public function getSeparator( $key, $length ) {
		return $key + 1 == $length ? '' : ', ';
	}

	public function render( $arguments = '' ) {
		add_meta_box( 'lav_boost_info', esc_html__( 'LavBoost Info', 'up-sell-pro' ), array(
			$this,
			'renderTabs'
		), 'shop_order', 'normal', 'core' );
	}

	public function getFields(): array {
		return array(
			'name'   => 'order',
			'title'  => esc_html__( 'Order', 'up-sell-pro' ),
			'icon'   => 'fas fa-file-invoice',
			'fields' => array(

				array(
					'id'       => 'order_add_to_order',
					'type'     => 'checkbox',
					'title'    => esc_html__( 'Add to order', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Chose which data type need add on Order Page', 'up-sell-pro' ),
					'help'     => esc_html__( 'It shows Up-sell\Cross-sell recommendation and search query in Order Page to help you suggest smartly additional products  if you call to customer to confirm the order', 'up-sell-pro' ),
					'options'  => array(
						'search'   => esc_html__( 'Search queries', 'up-sell-pro' ),
						'viewed'   => esc_html__( 'Viewed products', 'up-sell-pro' ),
						'categories' => esc_html__( 'Category relations', 'up-sell-pro' ),
						'tags'      => esc_html__( 'Tag relations', 'up-sell-pro' ),
					),
					'default'  => array(
						'search',
						'viewed',
						'categories',
						'tags',
					),
				),

				array(
					'id'       => 'order_order_items',
					'type'     => 'slider',
					'title'    => esc_html__( 'Quantity of items', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Set up the max number of related products for Order Page for every data type', 'up-sell-pro' ),
					'default'  => 5,
					'min'      => 1,
					'max'      => 15,
					'step'     => 1,
				),

				array(
					'id'      => 'order_relation_order',
					'type'    => 'button_set',
					'title'   => esc_html__( 'Order by', 'up-sell-pro' ),
					'options'    => array(
						'rand'     => esc_html__( 'Random', 'up-sell-pro' ),
						'date'     => esc_html__( 'Date', 'up-sell-pro' ),
						'modified' => esc_html__( 'Modified', 'up-sell-pro' ),
					),
					'default'    => 'rand',
				),

			)
		);
	}
}
