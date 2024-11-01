<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use traits\TLavBoostSingleton;

class LavBoostModuleGeneral extends LavBoostModule {
	use TLavBoostSingleton;

	public $DESTROY_COOKIE_TIME;

	public function run( $args = '' ) {
		$this->createSettingsTab();
		$this->DESTROY_COOKIE_TIME = time() - ( 60 * 60 * 24 * 7 );

		if ( $this->getValue( 'general_track_viewed' ) ) {
			add_action( 'template_redirect', array( $this, 'trackViewProducts' ), 21 );
		}

		if ( $this->getValue( 'general_track_search' ) ) {
			add_action( 'init', array( $this, 'enableTrackSearch' ) );
		} else {
			add_action( 'init', array( $this, 'disableTrackSearch' ) );
		}
	}

	public function enableTrackSearch() {
		if ( ! isset( $_COOKIE['lav-boost-search'] ) ) {
			setcookie( 'lav-boost-search', json_encode( [] ), time() + ( 60 * 60 * 24 * 7 ), '/' );
		}
	}

	public function disableTrackSearch() {
		setcookie( 'lav-boost-search', '', $this->DESTROY_COOKIE_TIME, '/' );
	}

	public function getData( $args = '' ) {
		if ( isset( $_COOKIE['lav-boost-search'] ) ) {
			return array_slice( json_decode( sanitize_text_field( stripslashes( $_COOKIE['lav-boost-search'] ) ) ), '-' . $this->getValue( 'general_keep_queries' ) );
		}
	}

	public function trackViewProducts() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		if ( is_active_widget( false, false, 'woocommerce_recently_viewed_products', true ) ) {
			return;
		}

		global $post;

		if ( empty( $_COOKIE['woocommerce_recently_viewed'] ) ) { // @codingStandardsIgnoreLine.
			$viewed_products = array();
		} else {
			if ( function_exists( 'wp_parse_id_list' ) ) {
				$viewed_products = wp_parse_id_list( (array) explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) ); // @codingStandardsIgnoreLine.
			}
		}

		// Unset if already in viewed products list.
		$keys = array_flip( $viewed_products );

		if ( isset( $keys[ $post->ID ] ) ) {
			unset( $viewed_products[ $keys[ $post->ID ] ] );
		}

		$viewed_products[] = $post->ID;

		if ( count( $viewed_products ) > 15 ) {
			array_shift( $viewed_products );
		}
		// Store for session only.
		if ( function_exists( 'wc_setcookie' ) ) {
			wc_setcookie( 'woocommerce_recently_viewed', implode( '|', $viewed_products ) );
		}

	}

	public function getFields(): array {
		return array(
			'name'   => 'general',
			'title'  => esc_html__( 'General', 'up-sell-pro' ),
			'icon'   => 'fa fa-wrench',
			'fields' => array(

				array(
					'id'       => 'general_track_search',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Track search', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable tracing of user\'s search queries on the website', 'up-sell-pro' ),
					'default'  => '1',
					'help'     => esc_html__( 'It helps better understand what users looked for in the shop before placing an order', 'up-sell-pro' ),
				),

				array(
					'id'         => 'general_keep_queries',
					'type'       => 'slider',
					'title'      => esc_html__( 'Keep queries', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up the number of the latest search queries to keep', 'up-sell-pro' ),
					'default'    => 5,
					'min'        => 1,
					'max'        => 15,
					'step'       => 1,
					'dependency' => array( 'general_track_search', '==', '1', '', 'visible' ),
				),

				array(
					'id'       => 'general_track_viewed',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Track viewed products', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable tracking of viewed products', 'up-sell-pro' ),
					'default'  => '1',
					'help'     => 'It helps better understand which products  interesting user  shop before placing an order',
				),
			),
		);
	}
}
