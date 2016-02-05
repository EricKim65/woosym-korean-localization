<?php
//print_r (get_option( wskl_get_option_name( 'checkout_methods') ) );
$sym_pg_title = 'Inicis';
$sym_checkout_methods = get_option( wskl_get_option_name( 'checkout_methods' ) ) ;

foreach ( $sym_checkout_methods as $key => $value ) {
    //echo "key=". $key. ":value=". $value. "<br>";
    switch ($value) {

        case 'credit':
            add_action( 'plugins_loaded', 'init_inicis_credit' );
            function init_inicis_credit() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Inicis_Credit extends WC_Inicis_Common {
                    public $method = "credit";
                }
                function add_inicis_credit( $methods ) {
                    $methods[] = 'WC_Inicis_Credit' ;
                    return $methods;
                }
                 add_filter( 'woocommerce_payment_gateways', 'add_inicis_credit' );
            }
            break;

        case 'remit':
             add_action( 'plugins_loaded', 'init_inicis_remit' );
            function init_inicis_remit() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Inicis_Remit extends WC_Inicis_Common {
                    public $method = "remit";
                }
                function add_inicis_remit( $methods ) {
                    $methods[] = 'WC_Inicis_Remit';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_inicis_remit') ;
            }
            break;

        case 'virtual':
            add_action( 'plugins_loaded', 'init_inicis_virtual' );
            function init_inicis_virtual() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Inicis_Virtual extends WC_Inicis_Common {
                    public $method = "virtual";
                }
                function add_inicis_virtual( $methods ) {
                    $methods[] = 'WC_Inicis_Virtual';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_inicis_virtual') ;
            }
            break;

        case 'mobile':
            add_action( 'plugins_loaded', 'init_inicis_mobile' );
            function init_inicis_mobile() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Inicis_Mobile extends WC_Inicis_Common {
                    public $method = "mobile";
                }
                function add_inicis_mobile( $methods ) {
                    $methods[] = 'WC_Inicis_Mobile';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_inicis_mobile') ;
            }
            break;
    }
}
