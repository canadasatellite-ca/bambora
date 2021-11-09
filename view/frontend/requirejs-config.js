var config = {
	map: {'*': {'Magento_Checkout/js/model/error-processor':'CanadaSatellite_Bambora/js/model/error-processor'}}
	,config: {mixins: {
		// 2021-11-03
		// 1) «Phone number must be between 7 and 32 characters long»: https://github.com/canadasatellite-ca/bambora/issues/15
		// 1) "`Amasty_Checkout`: prevent customers from entering obviously invalid phone numbers on the frontend checkout page":
		// https://github.com/canadasatellite-ca/site/issues/259
		'Magento_Checkout/js/view/billing-address': {'CanadaSatellite_Bambora/Magento_Checkout/js/view/billing-address': true}
	}}
};