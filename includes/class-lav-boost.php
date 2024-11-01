<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use modules\LavBoostModuleLoader;
use modules\LavBoostModuleMultipleAddToCart;

class Lav_Boost {

	protected $loader;

	protected $plugin_name;

	protected $version;

	public function __construct() {
		if ( defined( 'UP_SELL_PRO_VERSION' ) ) {
			$this->version = UP_SELL_PRO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'lav-boost';
		$this->set_locale();
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lav-boost-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-lav-boost-admin.php';

		/**
		 * The libs.
		 */

		if ( ! class_exists( 'CSF' ) && file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'libs/codestar-framework/codestar-framework.php' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'libs/codestar-framework/codestar-framework.php';
		}

		if(class_exists( 'CSF' )){
			CSF::createOptions( LAV_BOOST_PREFIX, LAV_BOOST_CONFIG );
		}

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-lav-boost-public.php';

		/**
		 * The classes autoload
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

		LavBoostModuleLoader::register();
		LavBoostModuleMultipleAddToCart::getInstance();
		$this->loader = new Lav_Boost_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Lav_Boost_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		load_textdomain( 'up-sell-pro', LAV_BOOST_LANG . '-' . determine_locale() . '.mo' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Lav_Boost_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Lav_Boost_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Lav_Boost_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
