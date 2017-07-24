/**
 * BtciPay payment method model
 *
 * @category    BtciPay
 * @package     BtciPay_Merchant
 * @author      BtciPay
 * @copyright   BtciPay (https://btci.com)
 * @license     https://github.com/bheema-bhx/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
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
                type: 'btci_merchant',
                component: 'BtciPay_Merchant/js/view/payment/method-renderer/btci-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
