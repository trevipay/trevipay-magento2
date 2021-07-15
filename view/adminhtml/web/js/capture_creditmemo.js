define([
    'jquery',
    'mage/translate',
    'domReady!',
], function ($, $t) {
    'use strict';

    return function (argConfig) {
        var defaults = {
            container: '#creditmemo_item_container'
        };

        var config = $.extend({}, defaults, argConfig);

        var removeCaptureOffline = function () {
            $("button[data-ui-id='order-items-submit-offline']").remove();
            $("button[data-ui-id='order-items-submit-button'][title='" + $t('Refund Offline') + "']").remove();
        };

        var observer = new MutationObserver(removeCaptureOffline);

        observer.observe($(config.container)[0], {attributes: false, childList: true, subtree: true});

        removeCaptureOffline();
    };
});
