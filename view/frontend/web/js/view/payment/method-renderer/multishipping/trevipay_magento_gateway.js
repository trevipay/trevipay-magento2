/*browser:true*/
define([
    'ko',
    'jquery',
    'mage/translate',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'TreviPay_TreviPayMagento/js/model/trevipay_magento_maxlength'
], function (
    ko,
    $,
    $t,
    PriceUtils,
    Component,
    customer,
    quote,
    maxLengthModel
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'TreviPay_TreviPayMagento/payment/multishipping/form',
            trevipayPoNumber: '',
            trevipayNotes: ''
        },

        initialize: function () {
            this._super()
                .observe([
                    'trevipayPoNumber',
                    'trevipayNotes'
                ]);

            quote.paymentMethod.subscribe(this._setMultiShippingStateTreviPayMagentoOff, this, 'beforeChange');
            quote.paymentMethod.subscribe(this._setMultiShippingStateTreviPayMagentoOn, this, 'change');
        },

        treviPaySectionClick: function () {
            window.location.href = window.checkoutConfig.payment.trevipay_magento.treviPaySectionUrl;
        },

        creditCurrencyCode: function () {
            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_currency')) {
                return;
            }

            return customer.customerData.custom_attributes.trevipay_m2_currency.value;
        },

        creditApprovedLimit: function () {
            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_credit_limit')) {
                return 0;
            }

            return customer.customerData.custom_attributes.trevipay_m2_credit_limit.value;
        },

        creditBalance: function () {
            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_credit_balance')) {
                return 0;
            }

            return customer.customerData.custom_attributes.trevipay_m2_credit_balance.value;
        },

        creditAuthorized: function () {
            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_credit_authorized')) {
                return 0;
            }

            return customer.customerData.custom_attributes.trevipay_m2_credit_authorized.value;
        },

        creditAvailable: function () {
            return this.creditApprovedLimit() - this.creditBalance() - this.creditAuthorized();
        },

        getPaymentMethodName: function() {
            return  window.checkoutConfig.payment.trevipay_magento.paymentMethodName;
        },

        formatPrice: function(value) {
            var priceFormat = Object.assign(
                {},
                window.checkoutConfig.priceFormat,
                { pattern: '%s ' + this.creditCurrencyCode() }
            );

            return PriceUtils.formatPrice(value, priceFormat, false);
        },

        isNotRegisteredBuyer: function () {
            return !(customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_status')
            );
        },

        /* eslint-disable max-len */
        isRegisteredButNotActiveBuyer: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_status')
                && customer.customerData.custom_attributes.trevipay_m2_status.value !== window.checkoutConfig.payment.trevipay_magento.buyerStatusActiveOptionId;
        },
        /* eslint-enable max-len */

        getMaxLengthForPurchaserOrderNumber: function () {
            return maxLengthModel.getMaxLength('trevipay_po_number', 200);
        },

        getMaxLengthForNotes: function () {
            return maxLengthModel.getMaxLength('trevipay_notes', 1000);
        },

        getPaymentMethodImageLocalPath: function() {
            return window.checkoutConfig.payment.trevipay_magento.paymentMethodImageLocalPath;
        },

        _setMultiShippingStateTreviPayMagentoOff: function (previousPaymentMethod) {
            if (previousPaymentMethod
                && previousPaymentMethod.method === this.getCode()) {
                $('#payment-continue').attr('disabled', false);
            }
        },

        _setMultiShippingStateTreviPayMagentoOn: function (newPaymentMethod) {
            if (newPaymentMethod
                && newPaymentMethod.method === this.getCode()
                && this.isRegisteredButNotActiveBuyer()) {
                $('#payment-continue').attr('disabled', true);
            }
        },

        // Translations

        tYouAreNotRegistered: function() {
            return $t(
                "You are not yet a registered TreviPay user. You will be redirected to the TreviPay credit application"
                + " form after placing this order.  Your order will be processed after approval of your TreviPay "
                + "credit application and TreviPay user account is created."
            ).replaceAll('%1', this.getPaymentMethodName());
        },

        tPaymentMethodNotAvailableToYou: function() {
            return $t('TreviPay payment method is currently not available to you. Please visit the '
            + 'TreviPay section to find more details.').replaceAll('%1', this.getPaymentMethodName());
        },
    });
});
