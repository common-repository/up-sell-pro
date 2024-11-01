<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use traits\TLavBoostSingleton;
use Elementor\Plugin;

class LavBoostModuleElementor extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();
        // Register widget scripts

		if ( $this->getValue( 'elementor_flash_sale' ) && UP_SELL_PRO_PAID ) {
			add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ] );
			// Register custom category widgets
			add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ] );
			// Register the widgets.
			add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		}
	}


	private function include_widgets_files() {
		require_once LAV_BOOST_ROOT . 'includes/elementor-addons/widgets/class-elementor-lav-boost-flash-sale.php';
	}

	public function widget_scripts() {
		wp_register_style( 'elementor-addons', LAV_BOOST_URL . 'public/css/elementor-addons.css', array(), '1.0.0', 'all' );
		wp_register_script( 'elementor-init', LAV_BOOST_URL . 'public/js/elementor-init.js', array(), '1.0.0', true );
		wp_register_script( 'flipper-responsive', LAV_BOOST_URL . 'public/js/jquery.flipper-responsive.js', array(), null, true );
	}

	public function register_widgets() {
		$this->include_widgets_files();
		Plugin::instance()->widgets_manager->register( new LalBoostFlashSale() );
	}

	public function add_elementor_widget_categories( $elements_manager ) {

		$elements_manager->add_category(
			'lav-boost-addons-category',
			[
				'title' => __( 'LavBoost', 'up-sell-pro' ),
				'icon' => 'fa fa-plug',
			]
		);
	}


	public function getData( $args = '' ) {

	}


	public function getFields(): array {
		return array(
			'name'   => 'elementor',
			'title'  => esc_html__( 'Elementor', 'up-sell-pro' ),
			'icon'   => 'fas fa-th',
			'fields' => array(

				array(
					'id'       => 'elementor_flash_sale',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Flash sale', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable flash sale add-on', 'up-sell-pro' ),
					'default'  => '0',
				),

				// A Notice
				array(
					'type'       => 'notice',
					'style'      => 'info',
					'content'    => esc_html__( 'Flash sale addon available only paid version - ', 'up-sell-pro' ) . '<a href="' . esc_url( 'https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/' ) . '" target="_blank">' . esc_html__( 'LavBoost', 'up-sell-pro' ) . '</a>',
					'dependency' => array( 'elementor_flash_sale', '==', '1' ),
				),

				array(
					'type'       => 'content',
					'content'    => '<div class="image-content"><img src="' . esc_url( LAV_BOOST_URL . '/admin/img/19_elementor.jpg' ) . '" alt="Proofs"></div>',
					'dependency' => array( 'elementor_flash_sale', '==', '1' ),
				),
			),
		);
	}
}
