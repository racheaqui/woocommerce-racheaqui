<?php
/**
 * WC Rache Aqui! Gateway Class.
 *
 * Built the Rache Aqui! method.
 */
class WC_Rache_Aqui_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id             = 'rache-aqui';
		$this->icon           = apply_filters( 'woocommerce_rache_aqui_icon', plugins_url( 'images/credit-card.png', plugin_dir_path( __FILE__ ) ) );
		$this->has_fields     = false;
		$this->method_title   = __( 'Rache Aqui!', 'woocommerce-rache-aqui' );

		// API URLs.
		$this->api_url = '';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->email          = $this->get_option( 'email' );
		$this->token          = $this->get_option( 'token' );
		$this->method         = $this->get_option( 'method', 'direct' );
		$this->invoice_prefix = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->debug          = $this->get_option( 'debug' );

		// Actions.
		add_action( 'woocommerce_api_wc_rache_aqui_gateway', array( $this, 'check_ipn_response' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $this->woocommerce_instance()->logger();
			}
		}
	}
}
