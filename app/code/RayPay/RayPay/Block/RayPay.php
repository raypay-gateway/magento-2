<?php
/**
 * RayPay payment gateway
 *
 * @developer hanieh729
 * @publisher RayPay
 * @copyright (C) 2021 RayPay
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 *
 * https://raypay.ir
 */
namespace RayPay\RayPay\Block;

use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Api\SearchCriteriaBuilder;

class RayPay extends \Magento\Framework\View\Element\Template
{
//\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;
    protected $_urlBuilder;
    protected $messageManager;
    protected $redirectFactory;
    protected $catalogSession;
    protected $customer_session;
    protected $order;
    protected $response;
    protected $session;
    protected $searchCriteriaBuilder;
    protected $transactionBuilder;
    protected $quote;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Session $customer_session,
        RedirectFactory $redirectFactory,
        \Magento\Framework\App\Response\Http $response,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        Template\Context $context,
        array $data
    ) {
        $this->customer_session = $customer_session;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
        $this->response = $response;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionBuilder = $transactionBuilder;
        $this->quote = $this->_checkoutSession->getQuote();
        parent::__construct($context, $data);
    }

    public function getOrderByIncrementId($incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId)->create();
        $orderData = null;
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->getList($searchCriteria);
            if ($order->getTotalCount()) {
                $orderData = $order->getItems();
            }
        } catch (Exception $exception)  {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $orderData;
    }
    public function getOrderByEntityId($entityId)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($entityId);
        }
        catch (Exception $exception)  {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $order;

    }

    public function changeStatus($status, $msg = null , $entityId)
    {
        $order  = $this->getOrderByEntityId($entityId);
            if (empty($msg)) {
                $order->setStatus($status);
            } else {
                $order->addStatusToHistory($status, $msg, true);
        }
        $order->save();

    }

    public function getOrderId()
    {
        $this->quote->reserveOrderId();
        $reservedOrderId = $this->quote->getReservedOrderId();
        $orderIdString = strval($reservedOrderId);
        $l=strlen($orderIdString);
        $realOrderId=str_pad($orderIdString -1, $l,"0", STR_PAD_LEFT);
        return $realOrderId;
    }

    private function getConfig($value)
    {
        return $this->_scopeConfig->getValue('payment/raypay/' . $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAfterOrderStatus()
    {
        return $this->getConfig('after_order_status');
    }

    public function getOrderStatus()
    {
        return $this->getConfig('order_status');
    }

    protected function raypay_get_failed_message($invoice_id)
    {
        return str_replace(["{invoice_id}"], [$invoice_id], $this->getConfig('failed_massage'));
    }

    protected function raypay_get_success_message($invoice_id)
    {
        return str_replace(["{invoice_id}"], [$invoice_id], $this->getConfig('success_massage'));
    }

    public function redirect()
    {
        if (!$this->getOrderId()) {
            $this->response->setRedirect($this->_urlBuilder->getUrl(''));
            return "";
        }
        $order_id = $this->getOrderId();

        $response['state'] = false;
        $response['result'] = "";

        $user_id = $this->getConfig('user_id');
        $acceptor_code = $this->getConfig('acceptor_code');


        $order  = $this->getOrderByIncrementId($order_id);
        foreach ($order as $orderData) {
            $entityId = $orderData->getEntityId();
            $incrementId = $orderData->getIncrementId();
            $amount = (int)$orderData->getGrandTotal();
            $billing  = $orderData->getBillingAddress();
            if ($billing->getEmail()) {
                $email = $billing->getEmail();
            } else {
                $email = $orderData->getCustomerEmail();
            }
            $name = $orderData->getBillingAddress()->getFirstname() . ' ' .$orderData->getBillingAddress()->getLastname();
            $mobile = $orderData->getShippingAddress()->getTelephone();
        }

        if (!empty($this->getConfig('currency')) && $this->getConfig('currency') == 1) {
            $amount *= 10;
        }
        $desc = "پرداخت فروشگاه مجنتو ۲ با شماره سفارش  " .$order_id;
        $redirectUrl = $this->_urlBuilder->getUrl('raypay/redirect/callback?order_id=' . $order_id . '&entity_id=' . $entityId .'&');
        $redirectUrl = rtrim($redirectUrl, "/");
        $invoice_id             = round(microtime(true) * 1000);

        if (empty($amount)) {
            $response['result'] = 'واحد پول انتخاب شده پشتیبانی نمی شود.';

            $this->changeStatus(Order::STATE_CLOSED, $response['result'] , $entityId);
            $this->messageManager->addErrorMessage($response['result']);

            $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
        }

        $params = array(
            'amount'       => strval($amount),
            'invoiceID'    => strval($invoice_id),
            'userID'       => $user_id,
            'redirectUrl'  => $redirectUrl,
            'factorNumber' => strval($order_id),
            'acceptorCode' => $acceptor_code,
            'email'        => $email,
            'mobile'       => $mobile,
            'fullName'     => $name,
            'comment'      => $desc
        );

        $ch = curl_init('https://api.raypay.ir/raypay/api/v1/Payment/getPaymentTokenWithUserID');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $result = curl_exec($ch);
        $result = json_decode($result);

        $http_status = $result->StatusCode;
        curl_close($ch);

        if ($http_status != 200 || empty($result) || empty($result->Data)) {
            $response['result'] = sprintf('خطا هنگام ایجاد تراکنش: %s. کد خطا: %s', $result->Message, $http_status);

            $this->changeStatus(Order::STATE_CLOSED, $response['result'] , $entityId);
            $this->messageManager->addErrorMessage($response['result']);

            $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
        } else {
            $this->_checkoutSession->setTestData($invoice_id);
            $this->changeStatus($this->getOrderStatus(), null, $entityId);
            $response['state'] = true;
            $access_token = $result->Data->Accesstoken;
            $terminal_id  = $result->Data->TerminalID;

            echo '<p style="color:#ff0000; font:18px Tahoma; direction:rtl;">در حال اتصال به درگاه بانکی. لطفا صبر کنید ...</p>';
            echo '<form name="frmRayPayPayment" method="post" action=" https://mabna.shaparak.ir:8080/Pay ">';
            echo '<input type="hidden" name="TerminalID" value="' . $terminal_id . '" />';
            echo '<input type="hidden" name="token" value="' . $access_token . '" />';
            echo '<input class="submit" type="submit" value="پرداخت" /></form>';
            echo '<script>document.frmRayPayPayment.submit();</script>';
            exit();
        }

        return $response;
    }

    public function callback()
    {
        $order_id = (string) $this->getRequest()->getParam('order_id');
        $invoice_id = (string) $this->getRequest()->getParam('?invoiceID');
        $entity_id = (int) $this->getRequest()->getParam('entity_id');
        $order = $this->getOrderByEntityId($entity_id);
        $response['state'] = false;
        $response['result'] = "";

        if (!$order || empty($order_id) || empty($invoice_id)) {
            $response['result'] = "سفارش پیدا نشده است.";
            $this->changeStatus(Order::STATE_CANCELED, $response['result'] , $entity_id);

            $this->messageManager->addErrorMessage($response['result']);
            $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
        } else {
                $verify_data = [
                    'order_id' => $order_id,
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.raypay.ir/raypay/api/v1/Payment/checkInvoice?pInvoiceID=' . $invoice_id);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verify_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                ]);

                $result = curl_exec($ch);
                $result = json_decode($result);
                $http_status = $result->StatusCode;
                curl_close($ch);

                if ($http_status != 200) {
                    $response['result'] = sprintf('خطا هنگام بررسی وضعیت تراکنش. کد خطا: %s, پیام خطا: %s', $http_status, $result->Message);
                    $this->changeStatus(Order::STATE_CANCELED, $response['result'] , $entity_id);

                    $this->messageManager->addErrorMessage($response['result']);
                    $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
                }

                $state           = $result->Data->State;
                $verify_order_id = $result->Data->FactorNumber;
                $verify_amount   = $result->Data->Amount;
                if (empty($verify_amount) || empty($verify_order_id) || $state != 1) {
                    $response['result'] = $this->raypay_get_failed_message($invoice_id);
                    $this->changeStatus(Order::STATE_CANCELED, $response['result'] , $entity_id);

                    $this->messageManager->addErrorMessage($response['result']);
                    $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
                } else {
                    $response['state'] = true;
                    $response['result'] = $this->raypay_get_success_message($invoice_id);
                    $this->order = $this->getOrderByEntityId($entity_id);
                    $this->addTransaction($this->order, $invoice_id);

                    $this->order->addStatusToHistory($this->getAfterOrderStatus(), sprintf('<pre>%s</pre>', print_r($result->Data, true)), false);
                    $this->order->save();

                    $this->changeStatus($this->getAfterOrderStatus(), $response['result'] , $entity_id);

                    $this->messageManager->addSuccessMessage($response['result']);
                    $this->response->setRedirect($this->_urlBuilder->getUrl(''));
                   //$this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/success'));
                }

        }

        setcookie("raypay_order_id", "", time() - 3600, "/");

        return $response;
    }

    public function addTransaction($order, $txnId)
    {
        $paymentData = [];
        $payment = $order->getPayment();
        $payment->setMethod('raypay');
        $payment->setLastTransId($txnId);
        $payment->setTransactionId($txnId);
        $payment->setIsTransactionClosed(0);
        $payment->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData]);
        $payment->setParentTransactionId(null);

        // Prepare transaction
        $transaction = $this->transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setFailSafe(true)
            ->setTransactionId($txnId)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData])
            ->build(Transaction::TYPE_CAPTURE);

        // Add transaction to payment
        $payment->addTransactionCommentsToOrder($transaction, __('The authorized TransactionId is %1.', $txnId));
        $payment->setParentTransactionId(null);

        // Save payment, transaction and order
        $payment->save();
        $order->save();
        $transaction->save()->close();

        return  $transaction->getTransactionId();
    }
}
