<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleSocialProof extends LavBoostModule {
	use TLavBoostSingleton;

	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'social_proof_enable' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'render' ), 99 );
		}
	}


	public function localize() {
		wp_localize_script( 'lav-boost', 'lavBoost', array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'nonce-lav-boost' ),
			'proofs'   => $this->getProofs(),
			'icon'     => LAV_BOOST_URL . '/public/img/checklist.svg',
			'verified' => $this->getValue( 'social_proof_title_middle' ),
			'text'     => $this->getValue( 'social_proof_title' ),
			'interval' => $this->getValue( 'social_proof_interval' ),
			'place'    => $this->getValue( 'social_proof_place' ),
			'theme'    => $this->getValue( 'social_proof_theme' ),
		) );
	}


	public function getProofs() {
		$dataProvider = new LavBoostDataLoader();
		$proofsType   = ! empty( $this->getValue( 'social_proof_source' ) )
			? $this->getValue( 'social_proof_source' )
			: 'fake-proofs';

		if ( ! UP_SELL_PRO_PAID ) {
			$proofsType = 'order-proofs';
		}

		$args = $this->getArgs();

		$proofs = $dataProvider->getData( $proofsType, $args );

		if ( ! empty( $proofs ) ) {
			return $proofs;
		}

		if ( empty( $proofs ) && $this->getValue( 'social_proof_add_if_empty' ) == 'yes' ) {
			$args['products'] = array();
			$args['type']     = 'product';

			return $dataProvider->getData( 'fake-proofs', $args );
		}

		return array();
	}


	public function getArgs() {
		return array(
			'add_random' => $this->getValue( 'social_proof_add_if_empty' ) == 'yes',
			'type'       => $this->getValue( 'social_proof_products_source' ),
			'products'   => $this->getValue( 'social_proof_source_products' ),
			'categories' => $this->getValue( 'social_proof_source_categories' ),
			'tags'       => $this->getValue( 'social_proof_source_tags' ),
		);
	}

	public function render( $args = '' ) {
		$array = is_array( $this->getValue( 'social_proof_pages' ) )
			? $this->getValue( 'social_proof_pages' )
			: array();

		if ( is_product() && in_array( 'product', $array ) ) {
			wp_enqueue_script( 'iziToast', LAV_BOOST_URL . 'public/js/iziToast.min.js', array(), '1.4.0', true );
			wp_enqueue_style( 'iziToast', LAV_BOOST_URL . 'public/css/iziToast.css', array(), '1.4.0', 'all' );
			$this->localize();
		}
		if ( ( is_shop() || is_product_category() ) && in_array( 'shop', $array ) ) {
			wp_enqueue_script( 'iziToast', LAV_BOOST_URL . 'public/js/iziToast.min.js', array(), '1.4.0', true );
			wp_enqueue_style( 'iziToast', LAV_BOOST_URL . 'public/css/iziToast.css', array(), '1.4.0', 'all' );
			$this->localize();
		}
	}


	public function getFields(): array {
		return array(
			'name'   => 'social_proof',
			'title'  => esc_html__( 'Social Proof', 'up-sell-pro' ),
			'icon'   => 'fas fa-people-arrows',
			'fields' => array(
				array(
					'id'       => 'social_proof_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable Social Proof', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable toasts with Social Proof.', 'up-sell-pro' ),
					'default'  => '0',
					'help'     => esc_html__( 'Display toasts with recent sales or fake data', 'up-sell-pro' ),
				),

				array(
					'id'         => 'social_proof_source',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Data', 'up-sell-pro' ),
					'options'    => array(
						'order-proofs' => esc_html__( 'Orders', 'up-sell-pro' ),
						'fake-proofs'  => esc_html__( 'Fakes', 'up-sell-pro' ),
					),
					'default'    => 'order-proofs',
					'dependency' => array( 'social_proof_enable', '==', '1', '', 'visible' ),
					'subtitle'   => esc_html__( 'Use sales for social proofs or fake data', 'up-sell-pro' ),
				),


				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'Fake data available only paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array(
						'social_proof_enable|social_proof_source',
						'==|==',
						'1|fake-proofs'
					),
				),

				array(
					'type'       => 'content',
					'content'    => '<div class="image-content"><img src="' . esc_url( LAV_BOOST_URL . '/admin/img/fake-data.jpg' ) . '" alt="Proofs"></div>',
					'dependency' => array(
						'social_proof_enable|social_proof_source',
						'==|==',
						'1|fake-proofs'
					),
				),

				array(
					'id'         => 'social_proof_place',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Place', 'up-sell-pro' ),
					'options'    => array(
						'bottomLeft'  => esc_html__( 'Left Bottom', 'up-sell-pro' ),
						'bottomRight' => esc_html__( 'Right Bottom', 'up-sell-pro' ),
					),
					'default'    => 'bottomLeft',
					'dependency' => array( 'social_proof_enable', '==', '1', '', 'visible' ),
				),


				array(
					'id'         => 'social_proof_theme',
					'type'       => 'button_set',
					'title'      => esc_html__( 'Theme', 'up-sell-pro' ),
					'options'    => array(
						'light' => esc_html__( 'Light', 'up-sell-pro' ),
						'dark'  => esc_html__( 'Dark', 'up-sell-pro' ),
					),
					'default'    => 'light',
					'dependency' => array( 'social_proof_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'         => 'social_proof_pages',
					'type'       => 'button_set',
					'multiple'   => true,
					'title'      => esc_html__( 'Pages', 'up-sell-pro' ),
					'options'    => array(
						'product' => esc_html__( 'Single Product', 'up-sell-pro' ),
						'shop'    => esc_html__( 'Shop Archive', 'up-sell-pro' ),
					),
					'default'    => 'shop',
					'dependency' => array( 'social_proof_enable', '==', '1', '', 'visible' ),
					'subtitle'   => esc_html__( 'Choose pages where show social proofs', 'up-sell-pro' ),
				),

				array(
					'id'         => 'social_proof_interval',
					'type'       => 'slider',
					'title'      => esc_html__( 'Interval', 'up-sell-pro' ),
					'subtitle'   => esc_html__( 'Set up interval between notifications', 'up-sell-pro' ),
					'default'    => 5000,
					'min'        => 3000,
					'max'        => 50000,
					'step'       => 100,
					'dependency' => array( 'social_proof_enable', '==', '1', '', 'visible' ),
				),


				// Style settings
				array(
					'type'    => 'heading',
					'content' => esc_html__( 'Headings', 'up-sell-pro' ),
				),
				array(
					'id'          => 'social_proof_title',
					'type'        => 'text',
					'title'       => esc_html__( 'Title', 'up-sell-pro' ),
					'subtitle'    => esc_html__( 'Title after user name', 'up-sell-pro' ),
					'default'     => esc_html__( 'purchased', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put text here', 'up-sell-pro' ),
					'dependency'  => array( 'social_proof_enable', '==', '1', '', 'visible' ),
				),

				array(
					'id'          => 'social_proof_title_middle',
					'type'        => 'text',
					'title'       => esc_html__( 'Verification text', 'up-sell-pro' ),
					'subtitle'    => esc_html__( 'Text after verification icon', 'up-sell-pro' ),
					'default'     => esc_html__( 'verified', 'up-sell-pro' ),
					'placeholder' => esc_html__( 'Put text here', 'up-sell-pro' ),
					'dependency'  => array( 'social_proof_enable', '==', '1', '', 'visible' ),
				),

			)
		);
	}

}
