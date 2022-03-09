define([
    'jquery',
], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            initialize: function () {
                // call the parent method
                this._super();
                $(document).ajaxComplete(function () {
                    $(".update-cart-item, a.action.delete").click(function () {
                        if (window.checkoutConfig) {
                            if (window.checkoutConfig.payment.trevipay_magento.isForceCheckout) {
                                $("#checkout-sign-in").fadeIn('slow');
                                $("#checkout-place-order").fadeOut('slow');
                            }
                        }
                    });
                });
            },
        });
    };
});

