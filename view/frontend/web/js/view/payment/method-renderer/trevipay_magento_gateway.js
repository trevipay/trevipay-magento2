/*browser:true*/
define([
    'jquery',
    'mage/translate',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Customer/js/model/customer',
    'TreviPay_TreviPayMagento/js/model/trevipay_magento_maxlength'
], function (
    $,
    $t,
    PriceUtils,
    Component,
    customer,
    maxLengthModel
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'TreviPay_TreviPayMagento/payment/form',
            trevipayPoNumber: '',
            trevipayNotes: '',
            redirectAfterPlaceOrder: false,
            appliedForCredit: false,
        },

        initObservable: function () {
            this._super()
                .observe([
                    'trevipayPoNumber',
                    'trevipayNotes'
                ]);

            // The `this` context must be bound to `applyForCredit` in this file.
            // In the `Apply for Credit` modal initialisation block in form.html,
            // the `this` context for `applyForCredit` is different,
            // and cannot be bound from within form.html.
            window.trevipay = { applyForCredit:  this.applyForCredit.bind(this) };

            return this;
        },

        getCode: function () {
            return 'trevipay_magento';
        },

        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'trevipay_po_number': this.trevipayPoNumber(),
                    'trevipay_notes': this.trevipayNotes()
                }
            };
        },

        isLoggedIn: function () {
            return customer.isLoggedIn();
        },

        signInClick: function () {
            window.location.href = window.checkout.customerLoginUrl;
        },

        treviPaySectionClick: function () {
            window.location.href = window.checkoutConfig.payment.trevipay_magento.treviPaySectionUrl;
        },

        buyerName: function () {
            const buyer = this.getTreviPayBuyer();
            if (buyer) return buyer.name;

            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_name')
                ?  customer.customerData.custom_attributes.trevipay_m2_buyer_name.value : null;
        },

        creditCurrencyCode: function () {
            const buyer = this.getTreviPayBuyer();
            if (buyer) return buyer.currencyCode;

            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_currency')) {
                return;
            }

            return customer.customerData.custom_attributes.trevipay_m2_buyer_currency.value;
        },

        creditApprovedLimit: function () {
            const buyer = this.getTreviPayBuyer();
            if (buyer) return buyer.creditLimit;

            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_credit_limit')) {
                return 0;
            }

            return customer.customerData.custom_attributes.trevipay_m2_buyer_credit_limit.value;
        },

        creditBalance: function () {
            const buyer = this.getTreviPayBuyer();
            if (buyer) return buyer.creditBalance;
            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_credit_balance')) {

                return 0;
            }

            return customer.customerData.custom_attributes.trevipay_m2_buyer_credit_balance.value;

        },

        creditAuthorized: function () {
            const buyer = this.getTreviPayBuyer();
            if (buyer) return buyer.creditAuthorized;

            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_credit_authorized')) {
                return 0;
            }

            return customer.customerData.custom_attributes.trevipay_m2_buyer_credit_authorized.value;
        },

        creditAvailable: function () {
            const buyer = this.getTreviPayBuyer();
            if (buyer) return buyer.creditAvailable;

            if (!customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_credit_available')) {
                return 0;
            }

            return customer.customerData.custom_attributes.trevipay_m2_buyer_credit_available.value;
        },

        formatPrice: function (value) {
            var priceFormat = Object.assign(
                {},
                window.checkoutConfig.priceFormat,
                { pattern: '%s ' + this.creditCurrencyCode() }
            );

            return PriceUtils.formatPrice(value, priceFormat, false);
        },

        isRegisteredCustomer: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && !this.shouldForgetMe();
        },

        /* eslint-disable max-len */
        isActiveCustomerStatus: function () {
            return this.isRegisteredCustomer()
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusActiveOptionId;
        },

        isActiveBuyerStatus: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_status')
                && customer.customerData.custom_attributes.trevipay_m2_buyer_status.value === window.checkoutConfig.payment.trevipay_magento.buyerStatusActiveOptionId;
        },

        isActiveBuyer: function () {
            return this.isActiveCustomerStatus()
                && this.isActiveBuyerStatus();
        },

        isActiveBuyerOrUnlinkedM2Customer: function () {
            return this.isActiveBuyer() || !this.isRegisteredCustomer();
        },

        isSignedIn: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_signed_in')
                && customer.customerData.custom_attributes.trevipay_m2_signed_in.value === 'true';
        },

        showCheckoutSignIn: function () {
            if (this.isBuyerSuspended()) return false;

            return !this.isRegisteredCustomer()
                || this.isForceCheckout() && !this.isSignedIn();
        },

        shouldForgetMe: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_forget_me')
                && customer.customerData.custom_attributes.trevipay_m2_forget_me.value === 'true';
        },

        getPaymentMethodName: function () {
            return window.checkoutConfig.payment.trevipay_magento.paymentMethodName;
        },

        getTreviPaySection: function () {
            return window.checkoutConfig.payment.trevipay_magento.treviPaySectionUrl;
        },

        getPaymentMethodImageLocalPath: function () {
            return window.checkoutConfig.payment.trevipay_magento.paymentMethodImageLocalPath;
        },

        isForceCheckout: function () {
            return window.checkoutConfig.payment.trevipay_magento.isForceCheckout;
        },

        checkoutSignInToLinkBuyer: function () {
            $.mage.redirect(window.checkoutConfig.payment.trevipay_magento.checkoutSignInToLinkBuyerUrl);
        },

        applyForCredit: function () {
            if (this.isPlaceOrderActionAllowed()) {
                this.placeOrder();
                this.appliedForCredit = true;
            }
        },

        placeOrderForLinkedBuyer: function () {
            this.placeOrder();
            this.redirectAfterPlaceOrder = !this.isForceCheckout();
        },

        afterPlaceOrder: function () {
            if (this.appliedForCredit) {
                $.mage.redirect(window.checkoutConfig.payment.trevipay_magento.applyForCreditUrl);
            } else if (this.isForceCheckout()) {
                $.mage.redirect(window.checkoutConfig.payment.trevipay_magento.signOutForForceCheckoutAfterPlaceOrderUrl);
            }
        },

        showMessage: function () {
            return this.getMessage() !== '';
        },

        hasMessage: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
            && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_message');
        },

        getMessage: function () {
            return this.hasMessage() ? customer.customerData.custom_attributes.trevipay_m2_message.value : '';
        },

        getMaxLengthForPurchaserOrderNumber: function () {
            return maxLengthModel.getMaxLength('trevipay_po_number', 200);
        },

        getMaxLengthForNotes: function () {
            return maxLengthModel.getMaxLength('trevipay_notes', 1000);
        },

        isBuyerDeleted: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_status')
                && customer.customerData.custom_attributes.trevipay_m2_buyer_status.value === window.checkoutConfig.payment.trevipay_magento.buyerStatusDeletedOptionId;
        },

        isBuyerSuspended: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_buyer_status')
                && customer.customerData.custom_attributes.trevipay_m2_buyer_status.value === window.checkoutConfig.payment.trevipay_magento.buyerStatusSuspendedOptionId;
        },

        hasAppliedForCredit: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusAppliedForCreditOptionId;
        },

        isCustomerSuspended: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusSuspendedOptionId;
        },

        isCreditApplicationCancelled: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusCancelledOptionId;
        },

        isCreditApplicationWithdrawn: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusWithdrawnOptionId;
        },

        isCreditApplicationPending: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusPendingOptionId;
        },

        isCreditApplicationPendingDirectDebit: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusPendingDirectDebitOptionId;
        },

        isCreditApplicationDeclined: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusDeclinedOptionId;
        },

        isCreditApplicationPendingSetup: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusPendingSetupOptionId;
        },

        isCreditApplicationPendingRecourse: function () {
            return customer.customerData.hasOwnProperty('custom_attributes')
                && customer.customerData.custom_attributes.hasOwnProperty('trevipay_m2_customer_status')
                && customer.customerData.custom_attributes.trevipay_m2_customer_status.value === window.checkoutConfig.payment.trevipay_magento.customerStatusPendingRecourseOptionId;
        },

        showViewForPreviouslyLinkedActiveBuyer: function () {
            return this.isBuyerDeleted() && this.isActiveCustomerStatus();
        },

        messageForPreviouslyLinkedActiveBuyer: function () {
            if (this.isBuyerSuspended()) {
                return this.tBuyerSuspended();
            }

            return this.tBuyerDeleted();
        },

        showViewForCanReapplyForCredit: function () {
            return !this.showViewForPreviouslyLinkedActiveBuyer()
                && (this.isCreditApplicationCancelled()
                    || this.isCreditApplicationWithdrawn()
                    || this.isCreditApplicationDeclined());
        },

        messageForCanReapplyForCredit: function () {
            if (this.isCreditApplicationCancelled()) {
                return this.tCreditApplicationCancelled();
            } else if (this.isCreditApplicationDeclined()) {
                return this.tCreditApplicationDeclined();
            }

            return this.tCreditApplicationWithdrawn();
        },

        showViewForMoreDetails: function () {
            return !this.showViewForPreviouslyLinkedActiveBuyer()
                && !this.showViewForCanReapplyForCredit();
        },

        messageForMoreDetails: function () {
            if (this.isCreditApplicationPending() || this.isCreditApplicationPendingRecourse()) {
                return this.tCreditApplicationPending();
            } else if (this.isCreditApplicationPendingSetup()) {
                return this.tCreditApplicationPendingSetup();
            } else if (this.isCreditApplicationPendingDirectDebit()) {
                return this.tCreditApplicationPendingDirectDebit();
            } else if (this.isCustomerSuspended() || this.isBuyerSuspended()) {
                return this.tCustomerSuspended();
            } else if (this.hasAppliedForCredit()) {
                return this.tCustomerAppliedForCredit();
            }

            return this.tPaymentMethodNotAvailableToYou();
        },

        // Translations

        // Refactoring translations into a translation helper method results in replacements with the default TreviPay
        // payment method name, rather than the white labelled payment method name, if any.
        tYouAreNotRegistered: function () {
            return $t('You are not yet a registered TreviPay user. You will be redirected to the TreviPay credit application form after placing this order. Your order will be processed after approval of your TreviPay credit application and TreviPay user account is created.').replaceAll('%1', this.getPaymentMethodName());
        },

        tPaymentMethodNotAvailableToYou: function () {
            return $t('TreviPay payment method is currently not available to you. Please visit the TreviPay section to find more details.').replaceAll('%1', this.getPaymentMethodName());
        },

        tApplyForTreviPay: function () {
            return $t('Apply for TreviPay').replaceAll('%1', this.getPaymentMethodName());
        },

        tBuyerDeleted: function () {
            return $t('Your TreviPay Account has been deleted. Please sign in again.').replaceAll('%1', this.getPaymentMethodName());
        },

        tBuyerSuspended: function () {
            return $t('Your TreviPay Account has been suspended. Please visit the TreviPay section to find more details.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCustomerAppliedForCredit: function () {
            return $t('You did not complete your TreviPay Credit Application. Please visit the TreviPay section to re-apply.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCustomerSuspended: function () {
            return $t('Your TreviPay Account has been suspended. Please visit the TreviPay section to find more details.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCreditApplicationDeclined: function () {
            return $t('Your TreviPay Credit Application has been declined. Please visit the TreviPay section for further details.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCreditApplicationCancelled: function () {
            return $t('Your TreviPay Credit Application has been cancelled.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCreditApplicationWithdrawn: function () {
            return $t('Your TreviPay Credit Application has been withdrawn.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCreditApplicationPending: function () {
            return $t('Your TreviPay Credit Application is pending. Please visit the TreviPay section to find more details.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCreditApplicationPendingDirectDebit: function () {
            return $t('Your TreviPay Credit Application is pending direct debit. Please visit the TreviPay section to find more details.').replaceAll('%1', this.getPaymentMethodName());
        },

        tCreditApplicationPendingSetup: function () {
            return $t('Your TreviPay Credit Application has been approved, and pending setup. <strong>[ACTION REQUIRED]</strong> Please check your email (including spam/junk) to complete the activation via the link within. You can visit the TreviPay section for further detail.').replaceAll('%1', this.getPaymentMethodName());
        },

        getTreviPayBuyer: function() {
            const buyerDetails = window.checkoutConfig.payment.trevipay_magento.buyerDetails;
            if (!buyerDetails) return null;

            return {
                creditLimit: buyerDetails.creditLimit,
                creditAuthorized: buyerDetails.creditAuthorized,
                creditAvailable: buyerDetails.creditAvailable,
                creditBalance: buyerDetails.creditBalance,
                name: buyerDetails.buyerName,
                currencyCode: buyerDetails.currencyCode,
            }
        }
    });
});
