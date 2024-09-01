var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/place-order': {
                'TreviPay_TreviPayMagento/js/mixin/place-order': true
            },
            'Magento_Checkout/js/view/minicart': {
                'TreviPay_TreviPayMagento/js/mixin/sidebar': true
            },
            'Magento_Ui/js/view/messages': {
                'TreviPay_TreviPayMagento/js/mixin/messages': true
            }
        }
    }
};
