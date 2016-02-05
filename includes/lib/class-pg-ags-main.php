<?php
//print_r (get_option( wskl_get_option_name( 'checkout_methods') ) );
$sym_pg_title = '올더게이트';
$sym_checkout_methods = get_option( wskl_get_option_name( 'checkout_methods') );

foreach ( $sym_checkout_methods as $key => $value ) {
    //echo "key=". $key. ":value=". $value. "<br>";
    switch ($value) {

        case 'credit':
            add_action( 'plugins_loaded', 'init_ags_credit' );
            function init_ags_credit() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Ags_Credit extends WC_Ags_Common {
                    public $method = "credit";
                }
                function add_ags_credit( $methods ) {
                    $methods[] = 'WC_Ags_Credit' ;
                    return $methods;
                }
                 add_filter( 'woocommerce_payment_gateways', 'add_ags_credit' );
            }
            break;

        case 'remit':
             add_action( 'plugins_loaded', 'init_ags_remit' );
            function init_ags_remit() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Ags_Remit extends WC_Ags_Common {
                    public $method = "remit";
                }
                function add_ags_remit( $methods ) {
                    $methods[] = 'WC_Ags_Remit';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_ags_remit') ;
            }
            break;

        case 'virtual':
            add_action( 'plugins_loaded', 'init_ags_virtual' );
            function init_ags_virtual() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Ags_Virtual extends WC_Ags_Common {
                    public $method = "virtual";
                }
                function add_ags_virtual( $methods ) {
                    $methods[] = 'WC_Ags_Virtual';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_ags_virtual') ;
            }
            break;

        case 'mobile':
            add_action( 'plugins_loaded', 'init_ags_mobile' );
            function init_ags_mobile() {
                if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
                class WC_Ags_Mobile extends WC_Ags_Common {
                    public $method = "mobile";
                }
                function add_ags_mobile( $methods ) {
                    $methods[] = 'WC_Ags_Mobile';
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'add_ags_mobile') ;
            }
            break;
    }
}




