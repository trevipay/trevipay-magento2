define([
  'jquery',
  'mage/utils/wrapper',
  'Magento_Checkout/js/model/error-processor',
], function($, wrapper, errorProcessor) {
  'use strict';

  return function(placeOrderService) {

    /** Override default place order action and add requestSource to payload */
    return wrapper.wrap(placeOrderService, function(originalFunction, serviceUrl, payload, messageContainer) {
      payload.requestSource = 'frontend_checkout';

      // Override handling of place order failure to redirect back to payment page instead of shipping - DX-1680
      var handlePlaceOrderFailure = function(response) {
        errorProcessor.process(response, messageContainer);
        setTimeout(function() {
          errorProcessor.redirectTo('#payment');
        }, 3000);
      }

      return originalFunction(serviceUrl, payload, messageContainer).fail(handlePlaceOrderFailure)
    });
  };
});
