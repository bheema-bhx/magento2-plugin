<?php
/**
 * BtciPay PlaceOrder controller
 *
 * @category    BtciPay
 * @package     BtciPay_Merchant
 * @author      BtciPay
 * @copyright   BtciPay (https://btci.com)
 * @license     https://github.com/bheema-bhx/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace BtciPay\Merchant\Controller\Payment;

use BtciPay\Merchant\Model\Payment as BtciPayPayment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;

class PlaceOrder extends Action
{
    protected $orderFactory;
    protected $btciPayment;
    protected $checkoutSession;

    /**
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param BtciPayPayment $btciPayment
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        BtciPayPayment $btciPayment
    )
    {
        parent::__construct($context);

        $this->orderFactory = $orderFactory;
        $this->btciPayment = $btciPayment;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $id = $this->checkoutSession->getLastOrderId();

        $order = $this->orderFactory->create()->load($id);

        if (!$order->getIncrementId()) {
            $this->getResponse()->setBody(json_encode(array(
                'status' => false,
                'reason' => 'Order Not Found',
            )));

            return;
        }

        $this->getResponse()->setBody(json_encode($this->btciPayment->getBtciPayRequest($order)));

        return;
    }
}
