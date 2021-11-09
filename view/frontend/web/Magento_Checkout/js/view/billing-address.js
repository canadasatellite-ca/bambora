// 2021-11-03
// 1) «Phone number must be between 7 and 32 characters long»: https://github.com/canadasatellite-ca/bambora/issues/15
// 1) "`Amasty_Checkout`: prevent customers from entering obviously invalid phone numbers on the frontend checkout page":
// https://github.com/canadasatellite-ca/site/issues/259
define([
	'mage/translate', 'uiRegistry'
], function($t, uiRegistry) {'use strict'; return function(sb) {return sb.extend({
	/**
	 * 2021-11-03
	 * @override
	 */	
	initialize: function() {
		this._super();
		var _this = this;
		uiRegistry.async(this.get('name') + '.form-fields.telephone')(_this.bindHandler.bind(_this));
	},

	/**
	 * 2021-11-06
	 * @private
	 * @param {Object} e UiClass
	 */
	bindHandler: function(e) {
		var _this = this;
		e.on('value', function() {
			clearTimeout(_this.timeout);
			_this.timeout = setTimeout(function() {_this.validate(e);}, 2000);
		});
	},
	
	/**
	 * @param {Object} e UiClass
	 * @return {*}
	 */
	validate: function(e) {
		var warnMessage;
		var r = e == null || e.value() == null;
		if (!r) {
			e.warn(null);
			// 2021-11-09 @TODO Implement a control similar to https://github.com/mage2pro/qiwi/blob/1.1.4/view/frontend/web/template/main.html
			var v = e.value().replace(/[\s+\-()]/g, '');
			r = v.length > 6 && v.length < 33;
			if (!r) {
				warnMessage = $t('The provided telephone seems to be invalid.');
				e.warn(warnMessage);
			}
		}
		return r;
	},	

	/**
	 * 2021-11-06
	 * @private
	 */	
	timeout: 0
})};});