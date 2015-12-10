function activate_license(key_type) {

    var input_id = '#' + key_type + '_license';
    var input = jQuery(input_id);

    if (input) {

        var key_value = input.val();

        if (key_value) {

            var data = {
                'key_type': key_type,
                'key_value': key_value,
                'site_url': activation_object.site_url,
                'action': 'activate_action',
                'activation_nonce': activation_object.activation_nonce
            };

            jQuery.ajax(
                activation_object.ajax_url, {
                    'data': data,
                    'method': 'post',
                    'success': function (response) {
                        location.reload(true);
                    }
                }
            );
        }
    }
}

jQuery(document).ready(function ($) {

    $('#payment_license_activation').click(function () {
        activate_license('payment');
    });

    $('#essential_license_activation').click(function () {
        activate_license('essential');
    });

    $('#extension_license_activation').click(function () {
        activate_license('extension');
    });

    $('#marketing_automation_license_activation').click(function () {
        activate_license('marketing_automation');
    });
});
