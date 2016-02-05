<?php
//print_r (get_option( wskl_get_option_name( 'checkout_methods') ) );
$sym_pg_title = 'KCP';
$sym_checkout_methods = get_option( wskl_get_option_name( 'checkout_methods' ) ) ;

foreach ( $sym_checkout_methods as $key => $value ) {
    //echo "key=". $key. ":value=". $value. "<br>";
    switch ($value) {

        case 'credit':
            add_action( 'plugins_loaded', 'init_kcp_credit' );
            function init_kcp_credit() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Kcp_Credit extends WC_Kcp_Common {
                    public $method = "credit";
                }
                function add_kcp_credit( $methods ) {
                    $methods[] = 'WC_Kcp_Credit' ;
                    return $methods;
                }
                 add_filter( 'woocommerce_payment_gateways', 'add_kcp_credit' );
            }
            break;

        case 'remit':
             add_action( 'plugins_loaded', 'init_kcp_remit' );
            function init_kcp_remit() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Kcp_Remit extends WC_Kcp_Common {
                    public $method = "remit";
                }
                function add_kcp_remit( $methods ) {
                    $methods[] = 'WC_Kcp_Remit';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_kcp_remit') ;
            }
            break;

        case 'virtual':
            add_action( 'plugins_loaded', 'init_kcp_virtual' );
            function init_kcp_virtual() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Kcp_Virtual extends WC_Kcp_Common {
                    public $method = "virtual";
                }
                function add_kcp_virtual( $methods ) {
                    $methods[] = 'WC_Kcp_Virtual';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_kcp_virtual') ;
            }
            break;

        case 'mobile':
            add_action( 'plugins_loaded', 'init_kcp_mobile' );
            function init_kcp_mobile() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Kcp_Mobile extends WC_Kcp_Common {
                    public $method = "mobile";
                }
                function add_kcp_mobile( $methods ) {
                    $methods[] = 'WC_Kcp_Mobile';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_kcp_mobile') ;
            }
            break;
    }
}




