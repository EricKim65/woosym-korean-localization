jQuery(function ($) {

    // triggered by woocommerce/assets/js/frontend/checkout.js
    // triggerHandler: checkout_place_order_*

    var iamport_checkout_types = [
        'checkout_place_order_wskl_iamport_credit',
        'checkout_place_order_wskl_iamport_remit',
        'checkout_place_order_wskl_iamport_virtual',
        'checkout_place_order_wskl_iamport_mobile',
        'checkout_place_order_wskl_iamport_kakao_pay'
    ];

    $('form[name="checkout"]').on(iamport_checkout_types.join(' '), function () {

        //woocommerce의 checkout.js의 기본동작을 그대로..woocommerce 버전바뀔 때마다 확인 필요
        var $form = $(this),
            gateway_name = $form.find('input[name="payment_method"]:checked').val();

        var pay_method = 'card',
            prefix = 'iamport_';

        if (gateway_name.indexOf(prefix) == 0) {
            pay_method = gateway_name.substring(prefix.length);
        }

        //카카오페이 처리
        if (pay_method == 'kakao') {
            pay_method = 'card';
        }

        $form.addClass('processing');
        var form_data = $form.data();

        if (1 !== form_data['blockUI.isBlocked']) {
            $form.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

        $.ajax({
            type: 'POST',
            url: wc_checkout_params.checkout_url,
            data: $form.serialize(),
            dataType: 'json',
            success: function (result) {
                try {
                    if (result.result === 'success') {
                        console.log(result.iamport);
                        //iamport process
                        var req_param = {
                            pay_method: result.iamport.pay_method,
                            escrow: result.iamport.escrow,
                            merchant_uid: result.iamport.merchant_uid,
                            name: result.iamport.name,
                            amount: parseInt(result.iamport.amount),
                            buyer_email: result.iamport.buyer_email,
                            buyer_name: result.iamport.buyer_name,
                            buyer_tel: result.iamport.buyer_tel,
                            buyer_addr: result.iamport.buyer_addr,
                            buyer_postcode: result.iamport.buyer_postcode,
                            vbank_due: result.iamport.vbank_due,
                            m_redirect_url: result.iamport.m_redirect_url,
                            custom_data: {woocommerce: result.order_id}
                        };

                        if (result.iamport.pg) {
                            req_param.pg = result.iamport.pg;
                        }

                        IMP.init(result.iamport.user_code);
                        IMP.request_pay(req_param, function (rsp) {
                            if (rsp.success) {
                                window.location.href = result.iamport.m_redirect_url + "&imp_uid=" + rsp.imp_uid;
                            } else {
                                alert(rsp.error_msg);
                                window.location.reload();
                            }
                        });
                    } else if (result.result === 'failure') {
                        throw 'Result failure';
                    } else {
                        throw 'Invalid response';
                    }
                } catch (err) {
                    // Reload page
                    if (result.reload === 'true') {
                        window.location.reload();
                        return;
                    }

                    // Remove old errors
                    $('.woocommerce-error, .woocommerce-message').remove();

                    // Add new errors
                    $form.prepend(err.message);

                    // Cancel processing
                    $form.removeClass('processing').unblock();

                    // Lose focus for all fields
                    $form.find('.input-text, select').blur();

                    // Scroll to top
                    $('html, body').animate({
                        scrollTop: ( $('form.checkout').offset().top - 100 )
                    }, 1000);

                    // Trigger update in case we need a fresh nonce
                    if (result.refresh === 'true')
                        $('body').trigger('update_checkout');

                    $('body').trigger('checkout_error');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(errorThrown);
                window.location.reload();
            }
        });

        return false; //기본 checkout 프로세스를 중단
    });
});