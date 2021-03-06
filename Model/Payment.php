<?php
/**
 * BtciPay payment method model
 *
 * @category    BtciPay
 * @package     BtciPay_Merchant
 * @author      BtciPay
 * @copyright   BtciPay (https://btci.com)
 * @license     https://github.com/bheema-bhx/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace BtciPay\Merchant\Model;

use BtciPay\BtciPay;
use BtciPay\Merchant as BtciPayMerchant;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class Payment extends AbstractMethod
{
    const BTCI_MAGENTO_VERSION = '1.0.0';
    const CODE = 'btci_merchant';

    protected $_code = 'btci_merchant';

    protected $_isInitializeNeeded = true;

    protected $urlBuilder;
    protected $btci;
    protected $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param BtciPayMerchant $btci
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @internal param ModuleListInterface $moduleList
     * @internal param TimezoneInterface $localeDate
     * @internal param CountryFactory $countryFactory
     * @internal param Http $response
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        BtciPayMerchant $btci,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = array()
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder = $urlBuilder;
        $this->btci = $btci;
        $this->storeManager = $storeManager;

        \BtciPay\BtciPay::config(array(
            'app_id' => $this->getConfigData('app_id'),
            'api_key' => $this->getConfigData('api_key'),
            'api_secret' => $this->getConfigData('api_secret'),
            'environment' => $this->getConfigData('sandbox_mode') ? 'sandbox' : 'live',
            'user_agent' => 'BtciPay - Magento 2 Extension v' . self::BTCI_MAGENTO_VERSION
        ));
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getBtciPayRequest(Order $order)
    {
        $token = substr(md5(rand()), 0, 32);

        $payment = $order->getPayment();
        $payment->setAdditionalInformation('btci_order_token', $token);
        $payment->save();

        $description = array();
        foreach ($order->getAllItems() as $item) {
            $description[] = number_format($item->getQtyOrdered(), 0) . ' × ' . $item->getName();
        }

        $params = array(
            'order_id' => $order->getIncrementId(),
            'price' => number_format($order->getGrandTotal(), 2, '.', ''),
            'buyer_email' => $order->getCustomerEmail(),
            'currency' => $order->getOrderCurrencyCode(),
            'receive_currency' => $this->getConfigData('receive_currency'),
            'callback_url' => ($this->urlBuilder->getUrl('btci/payment/callback') . '?token=' . $payment->getAdditionalInformation('btci_order_token')),
            'cancel_url' => $this->urlBuilder->getUrl('checkout/onepage/failure'),
            'success_url' => $this->urlBuilder->getUrl('checkout/onepage/success'),
            'title' => $this->storeManager->getWebsite()->getName(),
            'description' => join($description, ', ')
        );

        $cgOrder = \BtciPay\Merchant\Order::create($params);

        if ($cgOrder) {
            return array(
                'status' => true,
                'payment_url' => $cgOrder->payment_url
            );
        } else {
            return array(
                'status' => false
            );
        }
    }

    /**
     * @param Order $order
     */
    public function validateBtciPayCallback(Order $order)
    {
        try {
            if (!$order || !$order->getIncrementId()) {
                $request_order_id = (filter_input(INPUT_POST, 'order_id') ? filter_input(INPUT_POST, 'order_id') : filter_input(INPUT_GET, 'order_id'));

                throw new \Exception('Order #' . $request_order_id . ' does not exists');
            }

            $payment = $order->getPayment();
            $get_token = filter_input(INPUT_GET, 'token');
            $token1 = $get_token ? $get_token : '';
            $token2 = $payment->getAdditionalInformation('btci_order_token');

            if ($token2 == '' || $token1 != $token2) {
                throw new \Exception('Tokens do match.');
            }

            $request_id = (filter_input(INPUT_POST, 'id') ? filter_input(INPUT_POST, 'id') : filter_input(INPUT_GET, 'id'));
            $cgOrder = \BtciPay\Merchant\Order::find($request_id);

            if (!$cgOrder) {
                throw new \Exception('BtciPay Order #' . $request_id . ' does not exist');
            }

            if ($cgOrder->status == 'paid') {
                $order
                    ->setState(Order::STATE_PROCESSING, TRUE)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
                    ->save();
            } elseif (in_array($cgOrder->status, array('invalid', 'expired', 'canceled'))) {
                $order
                    ->setState(Order::STATE_CANCELED, TRUE)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CANCELED))
                    ->save();
            }
        } catch (\Exception $e) {
            $logger->exception($e);
            exit('Error occurred: ' . $e);
        }
    }
}
