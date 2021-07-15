define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    return function(argConfig) {
        var defaults = {
            container: '#invoice_item_container',
            dropdownName: 'invoice[capture_case]',
            option: 'offline'
        };

        var config = $.extend({}, defaults, argConfig);

        var removeCaptureOffline = function () {
            $('[name="' + config.dropdownName + '"] option[value="' + config.option + '"]').remove();
        };

        var observer = new MutationObserver(removeCaptureOffline);

        observer.observe( $(config.container)[0], {attributes: false, childList: true, subtree: true});

        removeCaptureOffline();
    };
});
