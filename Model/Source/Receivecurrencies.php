<?php
/**
 * Receive currencies Source Model
 *
 * @category    BtciPay
 * @package     BtciPay_Merchant
 * @author      BtciPay
 * @copyright   BtciPay (https://btci.com)
 * @license     https://github.com/bheema-bhx/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace BtciPay\Merchant\Model\Source;

class Receivecurrencies
{
    /**
     * @return array
     */
     public function toOptionArray()
     {
         return array(
            array('value' => 'btc', 'label' => 'Bitcoin (฿)'),
            array('value' => 'eur', 'label' => 'Euros (€)'),
            array('value' => 'usd', 'label' => 'US Dollars ($)') 
         );
     }}
