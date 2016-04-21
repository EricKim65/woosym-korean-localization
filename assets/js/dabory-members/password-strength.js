(function ($) {
    function checkPasswordStrength(password, confirm_password, strengthMeter, submitButton, blacklistArray, minPassValue) {

        submitButton.attr('disabled', 'disabled');
        blacklistArray = blacklistArray.concat(wp.passwordStrength.userInputBlacklist());
        strengthMeter.removeClass('short bad good strong');

        if (password.val().length == 0) {
            strengthMeter.html(passwordMeterObj.passwordEmpty);
            submitButton.hide();
            return 0;
        }

        var strengthValue = wp.passwordStrength.meter(password.val(), blacklistArray, confirm_password.val());

        /**
         * See https://github.com/dropbox/zxcvbn
         * See wp-includes/script-loader.php:wp_default_scripts()
         * handler: password-strength-meter
         */
        switch (strengthValue) {
            case 2:
                // score 2 - somewhat guessable: protection from unthrottled online attacks. (guesses < 10^8)
                strengthMeter.addClass('bad').html(pwsL10n.bad + passwordMeterObj.weakPasswordString);
                submitButton.addClass('button-disabled');
                break;
            case 3:
                // score 3 - safely unguessable: moderate protection from offline slow-hash scenario. (guesses < 10^10)
                strengthMeter.addClass('good').html(pwsL10n.good + passwordMeterObj.fairPasswordString);
                break;
            case 4:
                // score 4 - very unguessable: strong protection from offline slow-hash scenario. (guesses >= 10^10)
                strengthMeter.addClass('strong').html(pwsL10n.strong + passwordMeterObj.fairPasswordString);
                break;
            case 5:
                // custom score: password mismatch, if confirm_password is present.
                strengthMeter.addClass('short').html(pwsL10n.mismatch);
                break;
            default:
                // score 0 - too guessable: risky password. (guesses < 10^3)
                // score 1 - very guessable: protection from throttled online attacks. (guesses < 10^6)
                strengthMeter.addClass('short').html(pwsL10n.short + passwordMeterObj.weakPasswordString);
                break;
        }

        if (minPassValue <= strengthValue && strengthValue < 5) {
            submitButton.removeAttr('disabled');
            submitButton.show();
        } else {
            submitButton.hide();
        }

        return strengthValue;
    }

    if (passwordMeterObj.usePasswordStrengthMeter) {
        $('input[type="password"]').on('keyup', function () {
            checkPasswordStrength(
                $('input[name=' + passwordMeterObj.passwordName + ']'),
                $('input[name=' + passwordMeterObj.passwordConfirmName + ']'),
                $('span.password-strength-meter'),
                $('input[type="submit"]'),
                [],
                3
            );
        });
    }

})(jQuery);

