define(['uiComponent', 'Magento_Checkout/js/model/payment/renderer-list'], function(Component, rendererList) {
	'use strict';
	rendererList.push({component: 'CanadaSatellite_Bambora/js/view/payment/method-renderer/beanstream', type: 'beanstream'});
	return Component.extend({});
});