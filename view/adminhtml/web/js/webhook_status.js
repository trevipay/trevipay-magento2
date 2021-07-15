define([
    'jquery',
    'mage/storage',
    'mage/translate',
    'mage/url',
], function ($, storage, $t, urlBuilder) {
    'use strict';

    $.widget('wsnyc.webhookStatus', {
        options: {
            // Knockout.js dynamically generates the ids for Magento Fields in snake_case
            // (e.g., checkCreated, recreateCreated and created webhooks)
            checkCreatedWebhooksButtonId: '[id$="trevipay_magento_check_created_webhooks"]',
            checkCreateWebhooksLabel: 'label[for$="trevipay_magento_check_created_webhooks"]',
            checkCreatedWebhooksUrl: null,
            recreateCreatedWebhooksButtonId: '[id$="trevipay_magento_recreate_webhooks"]',
            recreateCreatedWebhooksLabel: 'label[for$="trevipay_magento_recreate_webhooks"]',
            recreateCreatedWebhooksUrl: null,
            // this id is explicitly specified in delete_webhooks.phtml
            deleteCreatedWebhooksButtonId: '[id$="trevipay_magento_delete_webhooks"]',
            deleteCreatedWebhooksLabel: 'label[for$="trevipay_magento_delete_webhooks"]',
            deleteCreatedWebhooksUrl: null,
            deleteConfirmMessage: "Are you sure you want to delete the webhooks?",
            createdWebhooksTextarea: '[id$="trevipay_magento_created_webhooks"]'
        },

        /**
         * Initialize store credit events
         * @private
         */
        _create: function () {
            var self = this;

            this._resetButtonsLabels();

            if (this.options.checkCreatedWebhooksUrl) {
                $(this.options.checkCreatedWebhooksButtonId)
                    .on('click', function () {
                        self._sendRequest(self.options.checkCreatedWebhooksUrl);
                    });
            }

            if (this.options.recreateCreatedWebhooksUrl) {
                $(this.options.recreateCreatedWebhooksButtonId)
                    .on('click', function () {
                        self._sendRequest(self.options.recreateCreatedWebhooksUrl);
                    });
            }

            this._setDeleteButtonDisabled($(this.options.createdWebhooksTextarea).val() === '[]');

            if (this.options.deleteCreatedWebhooksUrl) {
                $(this.options.deleteCreatedWebhooksButtonId)
                    .on('click', function () {
                        // eslint-disable-next-line no-alert
                        var isDelete = confirm(self.options.deleteConfirmMessage);

                        if (!isDelete) {
                            return;
                        }
                        self._sendRequest(self.options.deleteCreatedWebhooksUrl);
                    });
            }
        },

        _resetButtonsLabels: function(){
            $(this.options.checkCreateWebhooksLabel).attr('for', '');
            $(this.options.recreateCreatedWebhooksLabel).attr('for', '');
            $(this.options.deleteCreatedWebhooksLabel).attr('for', '');
        },

        _setDeleteButtonDisabled: function (disabled) {
            $(this.options.deleteCreatedWebhooksButtonId).attr('disabled', disabled);
        },

        _setMessage: function (messageType, text, status) {
            var messageWrapper = $(this.element).find('div[data-message-type="' + messageType + '"]');

            messageWrapper
                .text(text)
                .attr('class', '')
                .addClass('message')
                .addClass('message-' + status);

            if (text) messageWrapper.removeClass('hidden');
            else messageWrapper.addClass('hidden');
        },

        _setTooltip: function (text) {
            var messageWrapper = $(this.element).find('div[data-message-type="tooltip"]');

            messageWrapper.find('.admin__field-tooltip-content').html(text);

            if (text) messageWrapper.removeClass('hidden');
            else messageWrapper.addClass('hidden');
        },

        _setCreatedWebhooks: function (webhooks) {
            $(this.options.createdWebhooksTextarea).val(JSON.stringify(webhooks ? webhooks : []));
        },

        _setHtml: function (data) {
            if (data.hasOwnProperty('status') && data.status === 'success') {
                this._setMessage('created', data.messageCreated, data.statusCreated);
                this._setMessage('apikey', data.messageApiKey, data.statusApiKey);
                this._setMessage('baseUrl', data.messageBaseUrl, data.statusBaseUrl);
                this._setTooltip(data.tooltip);
                this._setCreatedWebhooks(data.createdWebhooks);
                this._setDeleteButtonDisabled(
                    typeof data.createdWebhooks !== 'undefined' && !data.createdWebhooks.length
                );
            } else if (data.hasOwnProperty('message')) {
                // eslint-disable-next-line no-alert
                alert(data.message);
            } else {
                // eslint-disable-next-line no-alert
                alert($t('There was an error processing your request.'));
            }
        },

        _setFail: function (response) {
            console.log(response);
        },

        _sendRequest: function (url) {
            var serviceUrl = urlBuilder.build(url);

            $('body').trigger('processStart');

            storage.get(serviceUrl)
                .done(this._setHtml.bind(this))
                .fail(this._setFail.bind(this))
                .always(function () {
                    $('body').trigger('processStop');
                });
        }

    });

    return $.wsnyc.webhookStatus;
});
