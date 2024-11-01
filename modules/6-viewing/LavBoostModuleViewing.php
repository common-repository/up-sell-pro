<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use traits\TLavBoostSingleton;

class LavBoostModuleViewing extends LavBoostModule {
	use TLavBoostSingleton;

	public function run( $args = '' ) {
		$this->createSettingsTab();

		$place = !empty( $this->getValue( 'viewing_and_sales_place' ) )
			? 'woocommerce_single_product_summary'
			: array();
		$value = $this->getValue( 'viewing_and_sales_place' ) == 'woocommerce_single_product_summary'
			? 5
			: 20;

		if ( $this->getValue( 'viewing_and_sales_enable' ) && UP_SELL_PRO_PAID ) {
			$features = $this->getValue('viewing_and_sales_features');
			if ( is_array( $features) && in_array('sales', $features) ) {
				add_action('woocommerce_product_get_stock_quantity', array( $this, 'setFakeSales' ), 10, 2);
			}
			add_action($place, array( $this, 'render' ), $value );
		}
	}

	public function getArgs($max = 9) {
		return rand(1, $max);
	}

	public function getCount($id) {
		$data = get_post_meta($id, 'lav_boost_fake_sales_count', true);
		return !empty($data) ? $data : 2;
	}

	public function getIconEye() {
		return LAV_BOOST_URL . 'public/img/eye.svg';
	}
	public function getIconCart() {
		return LAV_BOOST_URL . 'public/img/cart_download_icon.svg';
	}

	public function getFakeViewing() {
		global $product;
		$features = $this->getValue('viewing_and_sales_features');
		if ($product && is_array( $features) && in_array('viewing', $features) ) {
			return sprintf( '<div class="viewing-message"><img src="'. $this->getIconEye() .'" alt="viewing"><span class="viewing-count"> ' . esc_html__( '%1$s', 'up-sell-pro' ) . '</span><span class="viewing-text"> ' . esc_html__( '%2$s', 'up-sell-pro' ) . '</span></div>',
				($product->get_stock_quantity() + $this->getArgs()),
				$this->getValue( 'viewing_and_sales_viewing_title' ) );
		}else{
			return '';
		}
	}

	public function setFakeSales($value, $product) {

		if(empty($product)){
			return $value;
		}

		$product_id = $product->get_id();

		$current_datetime = current_time('timestamp');
		$last_update_timestamp = get_post_meta($product_id, 'lav_boost_fake_sales_last_update', true);

		if (empty($last_update_timestamp) || ($current_datetime - $last_update_timestamp) > 24 * 60 * 60) {
			$fake_sales_count = $this->getArgs(4);
			$current_count = $this->getCount($product_id);

			update_post_meta($product_id, 'lav_boost_fake_sales_count', $fake_sales_count + $current_count);
			update_post_meta($product_id, 'lav_boost_fake_sales_last_update', $current_datetime);
		}
		return $value;
	}

	public function getFakeSales( $product) {
		$features = $this->getValue('viewing_and_sales_features');

		if ($product && is_array( $features) && in_array('sales', $features) ) {
			$data = get_post_meta($product->get_id(), 'lav_boost_fake_sales_count', true);
			$count = !empty($data) ? $data : 12;
			return sprintf( '<div class="sales-message"><img src="'. $this->getIconCart() .'" alt="sales"><span class="sales-text"> ' . esc_html__( '%1$s', 'up-sell-pro' ) . '</span><span class="sales-count"> ' . esc_html__( '%2$s', 'up-sell-pro' ) . '</span></div>', $this->getValue( 'viewing_and_sales_sales_title' ), $count );
		}else{
			return '';
		}
	}


	public function render() {
		global $product;

		if ($product) {
			printf( '<div class="lav-boost product-marketing-data">' . esc_html__( '%1$s', 'up-sell-pro' ) . esc_html__( '%2$s', 'up-sell-pro' ) . '</div>', $this->getFakeViewing() , $this->getFakeSales($product) );
		}
	}


	public function getFields(): array {
		return array(
			'name'   => 'viewing',
			'title'  => esc_html__( 'Viewing & Sales', 'up-sell-pro' ),
			'icon'   => 'far fa-eye',
			'fields' => array(
				array(
					'id'       => 'viewing_and_sales_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable Viewing & Sales', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable fake Viewing & Sales message.', 'up-sell-pro' ),
					'default'  => '0',
				),

				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'This feature available only paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array(
						'viewing_and_sales_enable',
						'==',
						'1'
					),
				),

				array(
					'type'       => 'content',
					'content'    => '<div class="image-content"><img src="' . esc_url( LAV_BOOST_URL . '/admin/img/views.jpg' ) . '" alt="views"></div>',
					'dependency' => array(
						'viewing_and_sales_enable',
						'==',
						'1'
					),
				),
			)
		);
	}

}
