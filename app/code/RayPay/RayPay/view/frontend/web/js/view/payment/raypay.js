define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'raypay',
                component: 'RayPay_RayPay/js/view/payment/method-renderer/raypay-method'
            }
        );
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: true
            },
            afterPlaceOrder: function (data, event) {
                window.location.replace('raypay/redirect/index');
            }
        });
    }
);
