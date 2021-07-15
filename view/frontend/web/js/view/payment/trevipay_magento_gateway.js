/*browser:true*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'trevipay_magento',
                component: 'TreviPay_TreviPayMagento/js/view/payment/method-renderer/trevipay_magento_gateway'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
