/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'beanstream', // this should be the code you give in your Model file
                component: 'CanadaSatellite_Bambora/js/view/payment/method-renderer/beanstream'
                //component: 'Magento_Payment/js/view/payment/cc-form'
            }
        );
        /** Add view logic here if needed */
        // console.log('I am in the method-renderer');
        return Component.extend({});
    }
);