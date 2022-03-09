var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/place-order': {
                'TreviPay_TreviPayMagento/js/model/place-order-mixin': true
            },
            'Magento_Checkout/js/view/minicart': {
                'TreviPay_TreviPayMagento/js/model/sidebar': true
            }
        }
    }
};
