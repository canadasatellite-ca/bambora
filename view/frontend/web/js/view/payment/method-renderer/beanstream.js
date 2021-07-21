define(['Magento_Payment/js/view/payment/cc-form'], function (Component) {'use strict'; return Component.extend({
	defaults: {template: 'CanadaSatellite_Bambora/payment/cc-form'}
	,getCode: function() {return 'beanstream';}
	,isActive: function() {return true;}
});});