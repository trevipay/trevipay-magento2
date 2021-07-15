define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (placeOrderService) {

        /** Override default place order action and add requestSource to payload */
        return wrapper.wrap(placeOrderService, function (originalFunction, serviceUrl, payload, messageContainer) {
            payload.requestSource = 'frontend_checkout';

            return originalFunction(serviceUrl, payload, messageContainer);
        });
    };
});
