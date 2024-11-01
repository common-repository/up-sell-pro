<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleStyles extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();
	}

	public function render( $arguments = '' ) {

	}

	public function getFields(): array {
		return array(
			'name'   => 'styles',
			'title'  => esc_html__( 'Styles', 'up-sell-pro' ),
			'icon'   => 'fas fa-palette',
			'fields' => array(
				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'Styles settings available only paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
				),

				array(
					'type'       => 'content',
					'content'    => '<div class="image-content"><img src="' . esc_url( LAV_BOOST_URL . '/admin/img/styles.jpg' ) . '" alt="Proofs"></div>',
				),
			)
		);
	}
}
