<?php

/**
 * Plugin Name:       LavBoost Lite - All in One WooCommerce Related Products
 * Plugin URI:        https://first-design-company.com/plugins/lavboost-the-all-in-one-sales-increasing-woocommerce-plugin/
 * Description:       For customers who need to set up the most popular and effective tools to increase sales simple products in the WooCommerce store in a few clicks.
 * Version:           2.0.2
 * Author:            AliceThemes
 * Tags:              boost sales, order bump, upsell, woocommerce, donation
 * Tested up to:      6.6
 * Author URI:        https://first-design-company.com/
 * Donate link:       https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       up-sell-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Third-Party Libraries and Assets:

 * iziToast.js (Apache-2.0 license):
 * Usage: Included as a notification library for displaying alerts and messages.
 *
 * jquery.flipper-responsive (GPL-3.0 license):
 * Usage: Included as a responsive jQuery library for specific functionalities.
 *
 * js.cookie.js (MIT License):
 * Usage: Included as a JavaScript library for managing cookies.
 *
 * popupS.js (MIT License):
 * Usage: Included for creating Pop Ups.
 *
 * swiper.js (MIT License):
 * Usage: Included as a JavaScript library for creating interactive sliders and carousels.
 *
 * codestar-framework (GPL-2.0 license):
 * Usage: Included for creating settings inputs.
 *
 * Google Fonts (SIL Open Font License):
 * Usage: Jost Font Family.
 */

function up_sell_pro_lav_boost_check() {
	if ( current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-warning is-dismissible">
                <p>' . esc_html__( 'Please deactivate LavBoost Lite version and run LavBoost', 'up-sell-pro' ) . '</p>
             </div>';
	}
}
define( 'UP_SELL_PRO_VERSION', '2.0.1' );
define( 'UP_SELL_PRO_PAID', false );

if ( defined( 'LAV_BOOST_VERSION' ) ) {
	add_action( 'admin_notices', 'up_sell_pro_lav_boost_check' );
}else{
	define( 'LAV_BOOST_MODULES', plugin_dir_path( dirname( __FILE__ ) ) . 'up-sell-pro/modules' );
	define( 'LAV_BOOST_PREFIX', 'up-sell-pro-options' );
	define( 'LAV_BOOST_ROOT', plugin_dir_path( __FILE__ ) );
	define( 'LAV_BOOST_LANG', LAV_BOOST_ROOT . '/languages/up-sell-pro' );
	define( 'LAV_BOOST_URL', plugin_dir_url( __FILE__ ) );
	define( 'LAV_BOOST_CONFIG', array(
		'menu_title'      => 'LavBoost Lite',
		'menu_slug'       => 'up-sell-pro',
		'menu_type'       => 'menu',
		'menu_capability' => 'manage_options',
		'menu_icon'       => 'dashicons-chart-area',
		'menu_position'   => null,
		'menu_hidden'     => false,
		'show_bar_menu'   => false,
		'menu_parent'     => '',
		'sub_menu_title'  => '',
		'theme'           => 'light',
		'framework_title' => 'LavBoost Lite<br><small>by <a href="https://first-design-company.com/" target="_blank">First Design Company</a></small>',
	) );

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-lav-boost-activator.php
	 */
	function activate_lav_boost() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-lav-boost-activator.php';
		Lav_Boost_Activator::activate();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-lav-boost-deactivator.php
	 */
	function deactivate_lav_boost() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-lav-boost-deactivator.php';
		Lav_Boost_Deactivator::deactivate();
	}

	register_activation_hook( __FILE__, 'activate_lav_boost' );
	register_deactivation_hook( __FILE__, 'deactivate_lav_boost' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-lav-boost.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_lav_boost() {

		$plugin = new Lav_Boost();
		$plugin->run();

	}

	function lav_boost_general_admin_notice() {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-warning is-dismissible">
                <p>' . esc_html__( 'Sorry, LavBoost plugin works only with Woocommerce plugin. Please, deactivate LavBoost plugin or activate Woocommerce plugin', 'up-sell-pro' ) . '</p>
             </div>';
		}
	}

	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		run_lav_boost();
	} else {
		add_action( 'admin_notices', 'lav_boost_general_admin_notice' );
	}

}
