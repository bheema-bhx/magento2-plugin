<?php
/**
 * BtciPay Callback controller
 *
 * @category    BtciPay
 * @package     BtciPay_Merchant
 * @author      BtciPay
 * @copyright   BtciPay (https://btci.com)
 * @license     https://github.com/bheema-bhx/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace BtciPay\Merchant\Controller\Payment;

use BtciPay\Merchant\Model\Payment as BtciPayPayment;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;

class Callback extends Action
{
    protected $order;
    protected $btciPayment;

    /**
     * @param Context $context
     * @param Order $order
     * @param Payment|BtciPayPayment $btciPayment
     * @internal param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Order $order,
        BtciPayPayment $btciPayment
    )
    {
        parent::__construct($context);

        $this->order = $order;
        $this->btciPayment = $btciPayment;
    }

    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute()
    {
        $request_order_id = (filter_input(INPUT_POST, 'order_id') ? filter_input(INPUT_POST, 'order_id') : filter_input(INPUT_GET, 'order_id'));

        $order = $this->order->loadByIncrementId($request_order_id);
        $this->btciPayment->validateBtciPayCallback($order);

        $this->getResponse()->setBody('OK');
    }
}
