/*
* Override error message to increase display time
* DX-1680
* */
define([
  'ko',
  'jquery',
  'uiComponent',
  'jquery-ui-modules/effect-blind'
], function(ko, $, Component) {
  'use strict';
  var mixin = {
    /**
     * @param {Boolean} isHidden
     */
    onHiddenChange: function(isHidden) {
      if (isHidden) {
        setTimeout(function() {
          $(this.selector).hide('blind', {}, this.hideSpeed);
        }.bind(this), 15000); // Increase display time from 3 secs to 15 seconds
      }
    }
  };
  return function(target) {
    return target.extend(mixin);
  };
});
