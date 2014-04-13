<?php
/**
 * Plugin Name: WooCommerce rache aqui!
 * Plugin URI: http://www.racheaqui.com.br
 * Description: Gateway de pagamento rache aqui! para WooCommerce.
 * Author: rache aqui!
 * Author URI: http://www.racheaqui.com.br/
 * Version: 1.0
 * License: GPLv2 or later
 * Text Domain: wcracheaqui
 * Domain Path: /languages/
 */

/**
 * WooCommerce fallback notice.
 */
function wcracheaqui_woocommerce_fallback_notice() {
    $html = '<div class="error">';
        $html .= '<p>' . __( 'Plugin rache aqui! funciona melhor com a última versão de <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> !', 'wcracheaqui' ) . '</p>';
    $html .= '</div>';

    echo $html;
}

/**
 * Load functions.
 */
add_action( 'plugins_loaded', 'wcracheaqui_gateway_load', 0 );

function wcracheaqui_gateway_load() {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'wcracheaqui_woocommerce_fallback_notice' );

        return;
    }

    /**
     * Load textdomain.
     */
    load_plugin_textdomain( 'wcracheaqui', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    /**
     * Add the gateway to WooCommerce.
     *
     * @param array $methods
     *
     * @return array
     */
    add_filter( 'woocommerce_payment_gateways', 'wcracheaqui_add_gateway' );

    function wcracheaqui_add_gateway( $methods ) {
        $methods[] = 'WC_Rache_Aqui_Gateway';

        return $methods;
    }

    /**
     * WC Rache Aqui Gateway Class.
     *
     * Built the rache aqui method.
     */
    class WC_Rache_Aqui_Gateway extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         *
         * @return void
         */
        public function __construct() {
            global $woocommerce;

            $this->id             = 'racheaqui';
            $this->icon           = plugins_url( 'pagar_P.png', __FILE__ );
            $this->has_fields     = false;
            $this->payment_url    = 'https://testes.racheaqui.com.br';
            $this->method_title   = __( 'rache aqui!', 'wcracheaqui' );

            // Load the form fields.
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Define user set variables.
            $this->title          = $this->settings['title'];
            $this->description    = $this->settings['description'];
            $this->lojaID         = $this->settings['lojaid'];
            $this->num_raches     = $this->settings['num_raches'];
            $this->maximo_parcelas = $this -> settings['maximo_parcelas'];
            $this->email          = $this->settings['email'];
            $this->debug          = $this->settings['debug'];

            // Actions.
           // add_action( 'woocommerce_api_wc_racheaqui_gateway', array( &$this, 'check_ipn_response' ) );
          //  add_action( 'valid_racheaqui_ipn_request', array( &$this, 'successful_request' ) );
           add_action( 'woocommerce_receipt_racheaqui', array( &$this, 'receipt_page' ) );
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }

            // Valid for use.
            //$this->enabled = ( 'yes' == $this->settings['enabled'] ) && ! empty( $this->email ) && ! empty( $this->is_valid_for_use();

            // Filters.
            add_filter( 'woocommerce_available_payment_gateways', array( &$this, 'hides_when_is_outside_brazil' ) );



            // Active logs.
            if ( 'yes' == $this->debug ) {
                $this->log = $woocommerce->logger();
            }
        }

        /**
         * Check if this gateway is enabled and available in the user's country.
         *
         * @return bool
         */
        public function is_valid_for_use() {
            if ( ! in_array( get_woocommerce_currency(), array( 'BRL' ) ) ) {
                return false;
            }

            return true;
        }

        /**
         * Admin Panel Options.
         */
        public function admin_options() {

            echo '<h3>' . __( 'rache aqui!', 'wcracheaqui' ) . '</h3>';
            echo '<p>' . __( 'O plugin do rache aqui! envia as informações de pagamento do WooCommerce para o Gateway rache aqui!.', 'wcracheaqui' ) . '</p>';

            if ( ! $this->is_valid_for_use() ) {

                // Valid currency.
                echo '<div class="inline error"><p><strong>' . __( 'Gateway desabilitado', 'wcracheaqui' ) . '</strong>: ' . __( 'O rache aqui! não suporta moedas extrangeiras.', 'wcracheaqui' ) . '</p></div>';

            } else {

                // Generate the HTML For the settings form.
                echo '<table class="form-table">';
                $this->generate_settings_html();
                echo '</table>';
            }
        }

        /**
         * Initialise Gateway Settings Form Fields.
         *
         * @return void
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Ativar/Desativar', 'wcracheaqui' ),
                    'type' => 'checkbox',
                    'label' => __( 'Ativar o rache aqui!.', 'wcracheaqui' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Título', 'wcracheaqui' ),
                    'type' => 'text',
                    'description' => __( 'Este campo guarda a informação que o usuário visualizará.', 'wcracheaqui' ),
                    'default' => __( 'rache aqui!', 'wcracheaqui' )
                ),
                'description' => array(
                    'title' => __( 'Descrição', 'wcracheaqui' ),
                    'type' => 'textarea',
                    'description' => __( 'Este campo guarda a informação que o usuário visualizará durante o checkout.', 'wcracheaqui' ),
                    'default' => __( 'Pagamentos online com mais de um cartão de crédito.', 'wcracheaqui' )
                ),
                'email' => array(
                    'title' => __( 'E-mail', 'wcracheaqui' ),
                    'type' => 'text',
                    'description' => __( 'Por favor, coloque o e-mail cadastrado no rache aqui!.', 'wcracheaqui' ),
                    'default' => ''
                ),
                'lojaid' => array (
                    'title' => __('ID da Loja fornecido pelo rache aqui!','wcracheaqui'),
                    'type'=> 'text',
                    'description' => __('Por favor, entre com o id da loja.','wcracheaqui'),
                    'default' => ''
                ),
                'maximo_parcelas' => array (
                    'title' => __('Número máximo de parcelas permitido','wcracheaqui'),
                    'type' => 'text',
                    'description' => __('Por favor, entre com o número máximo de parcelas permitido.','wcracheaqui'),
                    'default' => 0
                ),
                'num_raches' => array(
                    'title' => __('Número máximo de raches permitido','wcracheaqui'),
                    'type' => 'text',
                    'description' => __('Por favor, entre com o número máximo de raches permitido.'),
                    'default' => 0
                ),
                'testing' => array(
                    'title' => __( 'Log', 'wcracheaqui' ),
                    'type' => 'title',
                    'description' => '',
                ),
                'debug' => array(
                    'title' => __( 'Gravar Log', 'wcracheaqui' ),
                    'type' => 'checkbox',
                    'label' => __( 'Ativar gravação do log', 'wcracheaqui' ),
                    'default' => 'no',
                    'description' => __( 'Grava requisiçõpes de log do rache aqui! para API do WooCommerce, dentro do arquivo <code>woocommerce/logs/racheaqui.txt</code>', 'wcracheaqui' ),
                )
            );

        }




        public function get_form_args( $order ) {

         
            // Fix Country.
            if ( 'BR' == $order->billing_country ) {
                $order->billing_country = 'BRA';
            }

            $args = array(
                'receiverEmail'             => $this->email,
                'currency'                  => get_woocommerce_currency(),
                'encoding'                  => 'UTF-8',

                // parametros do rache aqui
                'lojaID'                    => $this->lojaID,
                'valor_pedido'              => number_format(($order->get_total()),2,"",""),
                'pedidoID'                  => $order->id,
                'num_raches'                => $this->num_raches,
                'maximo_parcelas'           => $this->maximo_parcelas,


           
                // Payment Info.
                'reference'                 => $order->id
            );

            // If prices include tax or have order discounts, send the whole order as a single item.
            if ( 'yes' == get_option( 'woocommerce_prices_include_tax' ) || $order->get_order_discount() > 0 ) {

                // Discount.
                if ( $order->get_order_discount() > 0 ) {
                    $args['extraAmount'] = '-' . $order->get_order_discount();
                } else {
                    $args['extraAmount'] = '';
                }

                // rache aqui! has no option for tax inclusive pricing sadly. Pass 1 item for the order items overall.
                $item_names = array();

                if ( sizeof( $order->get_items() ) > 0 ) {
                    foreach ( $order->get_items() as $item ) {
                        if ( $item['qty'] ) {
                            $item_names[] = $item['name'] . ' x ' . $item['qty'];
                        }
                    }
                }

                $args['itemId1']          = 1;
                $args['itemDescription1'] = substr( sprintf( __( 'Order %s', 'wcracheaqui' ), $order->get_order_number() ) . ' - ' . implode( ', ', $item_names ), 0, 95 );
                $args['itemQuantity1']    = 1;
                // $args['itemAmount1']      = number_format( $order->get_total() - $order->get_shipping() - $order->get_shipping_tax() + $order->get_order_discount(), 2, '.', '' );
                $args['itemAmount1']      = number_format( $order->get_total() - $order->get_shipping() - $order->get_shipping_tax() + $order->get_order_discount(), 2, '.', '' );

                if ( ( $order->get_shipping() + $order->get_shipping_tax() ) > 0 ) {
                    $args['itemId2']          = 2;
                    $args['itemDescription2'] = __( 'Enviar via', 'wcracheaqui' ) . ' ' . ucwords( $order->shipping_method_title );
                    $args['itemQuantity2']    = '1';
                    $args['itemAmount2']      = number_format( $order->get_shipping() + $order->get_shipping_tax(), 2, '.', '' );
                }

            } else {

                // Cart Contents.
                $item_loop = 0;
                if ( sizeof( $order->get_items() ) > 0 ) {
                    foreach ( $order->get_items() as $item ) {
                        if ( $item['qty'] ) {

                            $item_loop++;

                            $product = $order->get_product_from_item( $item );

                            $item_name  = $item['name'];

                            $item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
                            if ( $meta = $item_meta->display( true, true ) ) {
                                $item_name .= ' - ' . $meta;
                            }

                            $args['itemId' . $item_loop]          = $item_loop;
                            $args['itemDescription' . $item_loop] = substr( sanitize_text_field( $item_name ), 0, 95 );
                            $args['itemQuantity' . $item_loop]    = $item['qty'];
                            $args['itemAmount' . $item_loop]      = $order->get_item_total( $item, false );

                        }
                    }
                }

                // Shipping Cost item.
                if ( $order->get_shipping() > 0 ) {
                    $item_loop++;
                    $args['itemId' . $item_loop]          = $item_loop;
                    $args['itemDescription' . $item_loop] = substr( __( 'Enviar via', 'wcracheaqui' ) . ' ' . ucwords( $order->shipping_method_title ), 0, 100 );
                    $args['itemQuantity' . $item_loop]    = '1';
                    $args['itemAmount' . $item_loop]      = number_format( $order->get_shipping(), 2, '.', '' );
                }

            }

            $args = apply_filters( 'woocommerce_racheaqui_args', $args );

            return $args;
        }

        /**
         * Generate the form.
         *
         * @param mixed $order_id
         *
         * @return string
         */
        public function generate_form( $order_id ) {
            global $woocommerce;

            $order = new WC_Order( $order_id );

            $args = $this->get_form_args( $order );

            if ( 'yes' == $this->debug ) {
                $this->log->add( 'racheaqui', 'Argumentos de pagamento para o pedido #' . $order_id . ': ' . print_r( $args, true ) );
            }

            $args_array = array();

            foreach ( $args as $key => $value ) {
                $args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
            }

            /* $woocommerce->add_inline_js( '
                jQuery("body").block({
                        message: "<img src=\"' . esc_url( $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif' ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />' . __( 'Obrigado pelo seu pedido. Estamos redirecionando seu pagamento para o rache aqui!.', 'wcracheaqui' ) . '",
                        overlayCSS:
                        {
                            background: "#fff",
                            opacity:    0.6
                        },
                        css: {
                            padding:         20,
                            textAlign:       "center",
                            color:           "#555",
                            border:          "3px solid #aaa",
                            backgroundColor: "#fff",
                            cursor:          "wait",
                            lineHeight:      "32px",
                            zIndex:          "9999"
                        }
                    })
                jQuery("#submit-payment-form").click();
            ' ); */

            return '<form action="' . esc_url( $this->payment_url ) . '" method="post" id="payment-form" target="_top">
                    ' . implode( '', $args_array ) . '
                    <input type="submit" class="button alt" id="submit-payment-form" value="' . __( 'Finalizar pagamento', 'wcracheaqui' ) . '" /> 
                </form>';

        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         *
         * @return array
         */
        public function process_payment( $order_id ) {

            $order = new WC_Order( $order_id );

            return array(
                'result'    => 'success',
                'redirect'  => add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) )
            );

        }

        /**
         * Output for the order received page.
         *
         * @return void
         */
        public function receipt_page( $order ) {
            global $woocommerce;

            //echo '<p>' . __( 'Obrigado pelo seu pedido.', 'wcracheaqui' ) . '</p>';

            echo $this->generate_form( $order );

            // Remove cart.
            $woocommerce->cart->empty_cart();
            
        }

        /**
         * Check ipn validity.
         *
         * @return bool
         */
 /*       public function check_ipn_request_is_valid() {

            if ( 'yes' == $this->debug ) {
                $this->log->add( 'racheaqui', 'Verificando pagamento com rache aqui! ...' );
            }

            $received_values = (array) stripslashes_deep( $_POST );
            $postdata = http_build_query( $received_values, '', '&' );
            $postdata .= '&Comando=validar&Token=' . $this->token;

            // Send back post vars.
            $params = array(
                'body'          => $postdata,
                'sslverify'     => false,
                'timeout'       => 30
            );

            // Post back to get a response.
            $response = wp_remote_post( $this->ipn_url, $params );

            if ( 'yes' == $this->debug ) {
                $this->log->add( 'racheaqui', 'Resposta do rache aqui!: ' . print_r( $response, true ) );
            }

            // Check to see if the request was valid.
            if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && ( strcmp( $response['body'], 'VERIFICADO' ) == 0 ) ) {

                if ( 'yes' == $this->debug ) {
                    $this->log->add( 'racheaqui', 'Resposta recebida do rache aqui!' );
                }

                return true;
            } else {
                if ( 'yes' == $this->debug ) {
                    $this->log->add( 'racheaqui', 'Resposta inválida do rache aqui!.' );
                }
            }

            return false;
        }*/

        /**
         * Check API Response.
         *
         * @return void
         */
/*        public function check_ipn_response() {

            @ob_clean();

            if ( ! empty( $_POST ) && ! empty( $this->token ) && $this->check_ipn_request_is_valid() ) {

                header( 'HTTP/1.1 200 OK' );

                do_action( 'valid_rache_aqui_ipn_request', $_POST );

            } else {

                wp_die( __( 'Requisição inválida ao rache aqui!', 'wcracheaqui' ) );

            }
        }*/

        /**
         * Successful Payment!
         *
         * @param array $posted
         *
         * @return void
         */
        /*public function successful_request( $received_values ) {

            $posted = (array) stripslashes_deep( $received_values );

            if ( ! empty( $posted['Referencia'] ) ) {
                $order_key = $posted['Referencia'];
                $order_id = (int) str_replace( $this->invoice_prefix, '', $order_key );

                $order = new WC_Order( $order_id );

                // Checks whether the invoice number matches the order.
                // If true processes the payment.
                if ( $order->id === $order_id ) {

                    $order_status = sanitize_title( $posted['StatusTransacao'] );

                    if ( 'yes' == $this->debug ) {
                        $this->log->add( 'racheaqui', 'Status do pedido #' . $order->id . ': ' . $posted['StatusTransacao'] );
                    }

                    switch ( $order_status ) {
                        case 'completo':

                            // Order details.
                            if ( ! empty( $posted['TransacaoID'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'ID Transacao', 'wcracheaqui' ),
                                    $posted['TransacaoID']
                                );
                            }
                            if ( ! empty( $posted['CliEmail'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'Email cliente', 'wcracheaqui' ),
                                    $posted['CliEmail']
                                );
                            }
                            if ( ! empty( $posted['CliNome'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'Nome do cliente', 'wcracheaqui' ),
                                    $posted['CliNome']
                                );
                            }
                            if ( ! empty( $posted['TipoPagamento'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'Tipo de pagamento', 'wcracheaqui' ),
                                    $posted['TipoPagamento']
                                );
                            }

                            $order->add_order_note( __( 'Pagamento completo.', 'wcracheaqui' ) );

                            break;
                        case 'aguardando-pagto':
                            $order->add_order_note( __( 'Aguardando pagamento.', 'wcracheaqui' ) );

                            break;
                        case 'aprovado':
                            $order->add_order_note( __( 'Pagamento aprovado', 'wcracheaqui' ) );

                            // Changing the order for processing and reduces the stock.
                            $order->payment_complete();

                            break;
                        case 'em-analise':
                            $order->update_status( 'on-hold', __( 'Pagamento aprovado, em revisão pelo rache aqui!.', 'wcracheaqui' ) );

                            break;
                        case 'cancelado':
                            $order->update_status( 'cancelled', __( 'Pedido cancelado', 'wcracheaqui' ) );

                            break;

                        default:
                            // No action xD.
                            break;
                    }
                }
            }
        } */

        /**
         * Adds error message when not configured the email.
         *
         * @return string Error Mensage.
         */
        public function mail_missing_message() {
            $html = '<div class="error">';
                $html .= '<p>' . sprintf( __( '<strong>Gateway Desabilitado</strong> Você deve informar seu email no cadastro do plugin. %sClique aqui para configurar!%s', 'wcracheaqui' ), '<a href="' . get_admin_url() . 'admin.php?page=woocommerce_settings&amp;tab=payment_gateways">', '</a>' ) . '</p>';
            $html .= '</div>';

            echo $html;
        }

        /**
         * Adds error message when not configured the token.
         *
         * @return string Error Mensage.
         */
        /*public function token_missing_message() {
            $html = '<div class="error">';
                $html .= '<p>' .sprintf( __( '<strong>Gateway Disabled</strong> You should inform your token in rache aqui. %sClick here to configure!%s', 'wcracheaqui' ), '<a href="' . get_admin_url() . 'admin.php?page=woocommerce_settings&amp;tab=payment_gateways">', '</a>' ) . '</p>';
            $html .= '</div>';

            echo $html;
        }*/

        /**
         * Hides the  with payment method with the customer lives outside Brazil
         *
         * @param  array $available_gateways Default Available Gateways.
         *
         * @return array                    New Available Gateways.
         */
        public function hides_when_is_outside_brazil( $available_gateways ) {

            if ( isset( $_REQUEST['country'] ) && 'BR' != $_REQUEST['country'] ) {

                // Remove standard shipping option.
                unset( $available_gateways['racheaqui'] );
            }

            return $available_gateways;
        }

    }
} // function

/**
 * Adds support to legacy IPN.
 *
 * @return void
 */

function rache_aqui_woocommerce_payment_complete( $order_id ) {
    error_log( "Pagamento recebido para o pedido: $order_id", 0 );
}
add_action( 'woocommerce_payment_complete','rache_aqui_woocommerce_payment_complete' );

function wc_racheaqui_legacy_ipn() {
    //if ( isset( $_POST['Referencia'] ) && ! isset( $_GET['wc-api'] ) ) {
    if ( isset( $_POST['lojaID'] ) && ! isset( $_GET['wc-api'] ) ) {
        global $woocommerce;

        $woocommerce->payment_gateways();

        do_action( 'woocommerce_api_wc_racheaqui_gateway' );
    }
}

add_action( 'init', 'wc_racheaqui_legacy_ipn' );
?>