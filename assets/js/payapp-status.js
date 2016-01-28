jQuery(function ($) {

        var pollInterval;
        var totalPollCount = 0;

        function waitAndProcessFeedback() {

            if (++totalPollCount > payAppStatus.pollingRetryMax) {
                clearInterval(pollInterval);
                window.href = payAppStatus.failureRedirect;
                return;
            }

            //noinspection JSUnresolvedVariable
            $.ajax(payAppStatus.ajaxUrl, {
                'method': 'GET',
                'success': function (response) {
                    // console.log(response);
                    if (response.success) {
                        //noinspection JSUnresolvedVariable
                        switch (response.order_status) {
                            case 'pending':
                                break;
                            case 'processing':
                            case 'completed':
                                clearInterval(pollInterval);
                                break;
                        }
                    } else {
                        clearInterval(pollInterval);
                    }

                    if (response.redirect) {
                        window.location = response.redirect;
                    }
                },
                'error': function () {
                    window.href = payAppStatus.failureRedirect;
                }
            });
        }

        // polling
        pollInterval = setInterval(waitAndProcessFeedback, 5000);
    }
);