jQuery(function ($) {

    var payAppWin;

    var checkout_form = jQuery('form[name="checkout"]');

    checkout_form.on('submit', function(){
        payAppWin = window.open(
            payapp_checkout.loadingPopupUrl,
            'payapp'
            //, 'toolbar=no,menubar=no,left=0,top=0'
        );
        payAppWin.blur();
    });

    checkout_form.on('checkout_place_order_payapp ', function () {

        var $form = $(this);
        var form_data = $form.data();

        $form.addClass('processing');

        if (1 !== form_data['blockUI.isBlocked']) {
            $form.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

        //noinspection JSUnresolvedVariable
        $.ajax({
            type: 'POST',
            url: wc_checkout_params.checkout_url,
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                console.log(response);
                try {
                    if (response.result === 'success') {

                        function processWhenPayAppWinReady() {
                            if(payAppWin) {

                                payAppWin.focus();
                                payAppWin.window.location = response.payApp.payurl;

                                //noinspection JSUnresolvedVariable
                                if ( -1 === response.redirect.indexOf( 'https://' ) || -1 === response.redirect.indexOf( 'http://' ) ) {
                                    window.location = response.redirect;
                                } else {
                                    window.location = decodeURI( response.redirect );
                                }

                            } else {
                                setTimeout(processWhenPayAppWinReady, 500);
                            }
                        }
                        processWhenPayAppWinReady();

                    } else {
                        if (response.result === 'failure') {
                            throw 'Result failure';
                        } else {
                            throw 'Invalid response';
                        }
                    }
                } catch (err) {
                    // Reload page
                    payAppWin.close();

                    if (response.reload === 'true') {
                        window.location.reload();
                        return;
                    }

                    $('.woocommerce-error, .woocommerce-message').remove();
                    $form.prepend(response.messages);
                    $form.removeClass('processing').unblock();
                    $form.find('.input-text, select').blur();
                    $('html, body').animate({
                        scrollTop: ( $('form.checkout').offset().top - 100 )
                    }, 1000);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(errorThrown);
                window.location.reload();
            }
        });

        return false;
    });
});