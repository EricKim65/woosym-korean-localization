function addToCart(itemId, quantity) {
    console.log('addToCart itemId: ' + itemId + ", quantity: " + quantity);
    console.log(woocommerce_params.wc_ajax_url);
    jQuery.ajax(
        wskl_direct_purchase_object.ajax_url, {
            'method': 'post',
            'data': {
                'add-to-cart': itemId,
                'quantity': quantity,
                'wc-ajax': 'wskl_direct_purchase_action'
            },
            'success': function (data) {
                location.href=wskl_direct_purchase_object.checkout_url;
            }
        }
    );
}

function onClickWsklDirectPurchase(e) {

    var itemId = jQuery('form.cart input[name="add-to-cart"]').val();
    var quantity = jQuery('form.cart input[name="quantity"]').val();

    e.preventDefault();
    addToCart(itemId, quantity);
    return false;
}

function init() {
    jQuery('#wskl-direct-purchase').click(onClickWsklDirectPurchase);
}

jQuery(document).ready(init);



