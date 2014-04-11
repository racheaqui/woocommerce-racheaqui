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
		global $woocommerce;

		$this->id             = 'rache-aqui';
		$this->icon           = apply_filters( 'woocommerce_rache_aqui_icon', plugins_url( 'images/credit-card.png', plugin_dir_path( __FILE__ ) ) );
		$this->has_fields     = false;
		$this->method_title   = __( 'Rache Aqui!', 'woocommerce-rache-aqui' );

		// API URLs.
		$this->production_url = 'https://pagamentos.racheaqui.com.br';
		$this->sandbox_url    = 'https://testes.racheaqui.com.br';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->store_id       = $this->get_option( 'store_id' );
		$this->split          = $this->get_option( 'split' );
		$this->installments   = $this->get_option( 'installments' );
		$this->invoice_prefix = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->sandbox        = $this->get_option( 'sandbox' );
		$this->force_error    = $this->get_option( 'force_error' );
		$this->debug          = $this->get_option( 'debug' );

		// Actions.
		// add_action( 'woocommerce_api_wc_rache_aqui_gateway', array( $this, 'check_ipn_response' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $woocommerce->logger();
			}
		}

		if ( is_admin() ) {
			// Checks if store_id is not empty.
			if ( empty( $this->store_id ) ) {
				add_action( 'admin_notices', array( $this, 'store_id_missing_message' ) );
			}

			// Checks that the currency is supported.
			if ( ! $this->using_supported_currency() ) {
				add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
			}
		}
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	protected function using_supported_currency() {
		return 'BRL' == get_woocommerce_currency();
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = ( 'yes' == $this->get_option( 'enabled' ) &&
					! empty( $this->store_id ) &&
					$this->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-rache-aqui' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Rache Aqui!', 'woocommerce-rache-aqui' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-rache-aqui' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-rache-aqui' ),
				'desc_tip'    => true,
				'default'     => __( 'Rache Aqui!', 'woocommerce-rache-aqui' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-rache-aqui' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-rache-aqui' ),
				'default'     => __( 'Pay two or more credit cards via Rache Aqui!', 'woocommerce-rache-aqui' )
			),
			'store_id' => array(
				'title'       => __( 'Rache Aqui! Store ID', 'woocommerce-rache-aqui' ),
				'type'        => 'text',
				'description' => __( 'Please enter your Rache Aqui! Store ID.', 'woocommerce-rache-aqui' ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'split' => array(
				'title'       => __( 'Max Credit Cards', 'woocommerce-rache-aqui' ),
				'type'        => 'select',
				'description' => __( 'Enter the maximum number of credit cards that the customers will can use. Must be less than or equal to the number registered in the Register of Retail, the opposite case is considered the number of the register.', 'woocommerce-rache-aqui' ),
				'desc_tip'    => true,
				'default'     => '',
				'options'     => array(
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10' => '10',
				)
			),
			'installments' => array(
				'title'       => __( 'Max Installments', 'woocommerce-rache-aqui' ),
				'type'        => 'text',
				'description' => __( 'Enter the maximum number of credit card installments. Must be less than or equal to the number registered in the Register of Retail, the opposite case is considered the number of the register.', 'woocommerce-rache-aqui' ),
				'desc_tip'    => true,
				'default'     => '',
				'options'     => array(
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
				)
			),
			'invoice_prefix' => array(
				'title'       => __( 'Invoice Prefix', 'woocommerce-rache-aqui' ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your Rache Aqui! account for multiple stores ensure this prefix is unqiue as Rache Aqui! will not allow orders with the same invoice number.', 'woocommerce-rache-aqui' ),
				'desc_tip'    => true,
				'default'     => 'WC-'
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-rache-aqui' ),
				'type'        => 'title',
				'description' => '',
			),
			'sandbox' => array(
				'title'       => __( 'Rache Aqui! Sandbox', 'woocommerce-rache-aqui' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Rache Aqui! sandbox', 'woocommerce-rache-aqui' ),
				'default'     => 'no',
				'description' => __( 'Rache Aqui! sandbox can be used to test payments.', 'woocommerce-rache-aqui' ),
			),
			'force_error' => array(
				'title'       => __( 'Simulate Error', 'woocommerce-rache-aqui' ),
				'type'        => 'checkbox',
				'label'       => __( 'Simulate invalid transaction', 'woocommerce-rache-aqui' ),
				'default'     => 'no',
				'description' => __( 'By default all requests are sent to the sandbox without cents to simulate purchase approved. Activating this option is sent along 1 cent to force an invalid transaction.', 'woocommerce-rache-aqui' ),
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-rache-aqui' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-rache-aqui' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Rache Aqui! events, such as API requests, inside %s', 'woocommerce-rache-aqui' ), '<code>woocommerce/logs/' . $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>' ),
			)
		);
	}

	/**
	 * Gets the admin url.
	 *
	 * @return string
	 */
	protected function admin_url() {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_rache_aqui_gateway' );
		}

		return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Rache_Aqui_Gateway' );
	}

	/**
	 * Adds error message when not configured the store_id.
	 *
	 * @return string Error Mensage.
	 */
	public function store_id_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Rache Aqui! Disabled', 'woocommerce-rache-aqui' ) . '</strong>: ' . sprintf( __( 'You should inform your Store ID. %s', 'woocommerce-rache-aqui' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woocommerce-rache-aqui' ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when an unsupported currency is used.
	 *
	 * @return string
	 */
	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>' . __( 'Rache Aqui! Disabled', 'woocommerce-rache-aqui' ) . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. The Rache Aqui! works only with <code>BRL</code>.', 'woocommerce-rache-aqui' ), get_woocommerce_currency() ) . '</p></div>';
	}
}
