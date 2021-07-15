define([], function () {
    'use strict';

    var getMaxLength = function (fieldName, defaultMaxLength) {
        var treviPayConfig = window.checkoutConfig.payment.trevipay_magento;

        return treviPayConfig[fieldName] && treviPayConfig[fieldName].maxlength
            ? treviPayConfig[fieldName].maxlength
            : defaultMaxLength;
    };

    return {
        getMaxLength: getMaxLength
    };
});
