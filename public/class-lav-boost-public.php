<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://alicethemes.com/
 * @since      1.0.0
 *
 * @package    Lav_Boost
 * @subpackage Lav_Boost/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Lav_Boost
 * @subpackage Lav_Boost/public
 * @author     AliceThemes <1stdesigncompany@gmail.com>
 */
class Lav_Boost_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Lav_Boost_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Lav_Boost_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/lav-boost-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'swiper', plugin_dir_url( __FILE__ ) . 'css/swiper-bundle.min.css', array(), '9.2.4', 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Lav_Boost_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Lav_Boost_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script( 'js-cookie', plugin_dir_url( __FILE__ ) . 'js/js.cookie.js', array(), '2.1.4', true );
		wp_enqueue_script( 'swiper', plugin_dir_url( __FILE__ ) . 'js/swiper-bundle.min.js', array(), '9.2.4', true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/lav-boost-public.js', array(
			'jquery',
			'js-cookie',
		), $this->version, true );
	}

}
