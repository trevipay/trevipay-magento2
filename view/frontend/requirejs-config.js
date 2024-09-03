var config = {
  config: {
    mixins: {
      'Magento_Checkout/js/model/place-order': {
        'TreviPay_TreviPayMagento/js/model/place-order-mixin': true
      },
      'Magento_Checkout/js/view/minicart': {
        'TreviPay_TreviPayMagento/js/model/sidebar-mixin': true
      },
      'Magento_Ui/js/view/messages': {
        'TreviPay_TreviPayMagento/js/model/messages-mixin': true
      }
    }
  }
};
