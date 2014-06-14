<?php
/**
 * Plugin Name: WooCommerce Rache Aqui!
 * Plugin URI: https://www.racheaqui.com.br/
 * Description: Allow your customers to pay with more than a single credit card using the Rache Aqui!
 * Author: claudiosanches, Rache Aqui!
 * Author URI: http://claudiosmweb.com/
 * Version: 2.0.1
 * License: GPLv2 or later
 * Text Domain: woocommerce-racheaqui
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_RacheAqui' ) ) :

/**
 * WooCommerce Rache Aqui! main class.
 */
class WC_RacheAqui {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '2.0.1';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin public actions.
	 */
	private function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			// Include the WC_RacheAqui_Gateway class.
			include_once 'includes/class-wc-racheaqui-gateway.php';

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-racheaqui' );

		load_textdomain( 'woocommerce-racheaqui', trailingslashit( WP_LANG_DIR ) . 'woocommerce-racheaqui/woocommerce-racheaqui-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-racheaqui', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param   array $methods WooCommerce payment methods.
	 *
	 * @return  array          Payment methods with Rache Aqui!.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_RacheAqui_Gateway';

		return $methods;
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Rache Aqui! depends on the last version of %s to work!', 'woocommerce-racheaqui' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', 'woocommerce-racheaqui' ) . '</a>' ) . '</p></div>';
	}
}

add_action( 'plugins_loaded', array( 'WC_RacheAqui', 'get_instance' ), 0 );

endif;
