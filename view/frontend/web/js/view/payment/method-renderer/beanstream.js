/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form' // instead of payment/default we are inheriting from payment/cc-form since, we want to use the default Magento CC Form
    ],
    function (Component) {
        'use strict';
        // console.log('I am in the beanstream.js file');
        return Component.extend({
            defaults: {
                //template: 'Magento_Payment/payment/cc-form' // Nothing fancy here. We want to use the default Magento CC Form
                template: 'CanadaSatellite_Bambora/payment/cc-form'
            },
             getCode: function() {
                return 'beanstream'; // Our payment module code. Should be the same as what we give in our Model
            },
            isActive: function() {
                return true;
            }
        });
    }
);

