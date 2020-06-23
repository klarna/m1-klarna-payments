<?php
/**
 * This file is part of the Klarna Core module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/**
 * Klarna core payment method abstract
 */
class Klarna_Core_Model_Payment_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Availability options
     */
    protected $_isInitializeNeeded = true;

    /**
     * Fraud status types
     */
    const FRAUD_STATUS_ACCEPTED = 'ACCEPTED';
    const FRAUD_STATUS_REJECTED = 'REJECTED';
    const FRAUD_STATUS_PENDING  = 'PENDING';

    /**
     * @var Klarna_Core_Helper_Data
     */
    protected $_helper;

    /**
     * Klarna_Core_Model_Payment_Method_Abstract constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_helper = Mage::helper('klarna_core');
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order   = $payment->getOrder();
        $store   = $order->getStore();
        if (0 >= $order->getGrandTotal()) {
            $stateObject->setState(Mage_Sales_Model_Order::STATE_NEW);
        } elseif ($this->_helper->getStoreApiVersionConfig($store)->getPaymentReview()) {
            $stateObject->setStatus('pending_payment');
            $stateObject->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
        } else {
            $stateObject->setStatus($payment->getMethodInstance()->getConfigData('order_status'));
            $stateObject->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
        }

        $stateObject->setIsNotified(false);

        $transactionId = $this->getPostPurchaseApi()->getReservationId();

        $payment->setTransactionId($transactionId)
            ->setIsTransactionClosed(0);

        if ($transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)) {
            $transaction->save();
        }

        return $this;
    }

    /**
     * Check partial capture availability
     *
     * @return bool
     */
    public function canCapturePartial()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getInfoInstance()->getOrder();

        if ($order && $order->getId()) {
            $canCapturePartialObject = new Varien_Object(
                array(
                'can_partial' => $this->_helper->getPartialPaymentSupport($order->getStore())
                )
            );

            $checkoutType = $this->_helper->getStoreApiTypeCode($order->getStore());
            $eventData    = array(
                'flag_object' => $canCapturePartialObject,
                'order'       => $order
            );

            Mage::dispatchEvent('klarna_core_payment_can_capture_partial', $eventData);
            Mage::dispatchEvent("klarna_core_payment_type_{$checkoutType}_can_capture_partial", $eventData);

            return $canCapturePartialObject->getCanPartial();
        }

        return parent::canCapturePartial();
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getInfoInstance()->getOrder();

        if ($order && $order->getId()) {
            $canInvoicePartialObject = new Varien_Object(
                array(
                'can_partial' => $this->_helper->getPartialPaymentSupport($order->getStore())
                )
            );

            $checkoutType = $this->_helper->getStoreApiTypeCode($order->getStore());
            $eventData    = array(
                'flag_object' => $canInvoicePartialObject,
                'order'       => $order
            );

            Mage::dispatchEvent('klarna_core_payment_can_refund_partial_per_invoice', $eventData);
            Mage::dispatchEvent("klarna_core_payment_type_{$checkoutType}_can_refund_partial_per_invoice", $eventData);

            return $canInvoicePartialObject->getCanPartial();
        }

        return parent::canRefundPartialPerInvoice();
    }

    /**
     * Get payment action for method
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return self::ACTION_AUTHORIZE;
    }

    /**
     * Fetch transaction info
     *
     * @param Mage_Payment_Model_Info $payment
     * @param string                  $transactionId
     *
     * @return array
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId)
    {
        $order = $payment->getOrder();
        $store = $this->getStore();

        if ($this->_helper->getStoreApiVersionOptions($store)->getPaymentReview()) {
            if (null === $transactionId) {
                $klarnaOrder   = Mage::getModel('klarna_core/order')->loadByOrder($order);
                $transactionId = $klarnaOrder->getReservationId();
            }

            $orderStatus = $this->getPostPurchaseApi()->getFraudStatus($transactionId);

            if ($orderStatus == 1) {
                $payment->setIsTransactionApproved(true);
            } elseif ($orderStatus == -1) {
                $payment->setIsTransactionDenied(true);
                if ($payment->getAuthorizationTransaction()) {
                    $payment->getAuthorizationTransaction()->closeAuthorization();
                }
            }
        }

        return array();
    }

    /**
     * Capture payment method
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $klarnaOrder = $this->getKlarnaOrder($payment->getOrder());

        if (!$klarnaOrder->getId() || !$klarnaOrder->getReservationId()) {
            Mage::throwException('Unable to capture payment for this order.');
        }

        $response = $this->getPostPurchaseApi()
            ->capture($klarnaOrder->getReservationId(), $amount, $payment->getInvoice());

        if (!$response->getIsSuccessful()) {
            Mage::throwException('Payment capture failed, please try again.');
        }

        if ($response->getTransactionId()) {
            $payment->setTransactionId($response->getTransactionId());
        }

        return $this;
    }

    /**
     * Cancel payment method
     *
     * @param Varien_Object $payment
     *
     * @return $this
     */
    public function cancel(Varien_Object $payment)
    {
        $klarnaOrder = $this->getKlarnaOrder($payment->getOrder());

        if (!$klarnaOrder->getId() || !$klarnaOrder->getReservationId()) {
            Mage::throwException('Unable to cancel payment for this order.');
        }

        if ($payment->getOrder()->hasInvoices()) {
            $response = $this->getPostPurchaseApi()->release($klarnaOrder->getReservationId());
        } else {
            $response = $this->getPostPurchaseApi()->cancel($klarnaOrder->getReservationId());
        }

        if (!$response->getIsSuccessful()) {
            Mage::throwException('Order cancellation failed, please try again.');
        }

        if ($response->getTransactionId()) {
            $payment->setTransactionId($response->getTransactionId());
        }

        return $this;
    }

    /**
     * Void payment
     *
     * Same as cancel
     *
     * @param Varien_Object $payment
     *
     * @return $this
     */
    public function void(Varien_Object $payment)
    {
        return $this->cancel($payment);
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $klarnaOrder = $this->getKlarnaOrder($payment->getOrder());

        if (!$klarnaOrder->getId() || !$klarnaOrder->getReservationId()) {
            Mage::throwException('Unable to refund payment for this order.');
        }

        $response = $this->getPostPurchaseApi()
            ->refund($klarnaOrder->getReservationId(), $amount, $payment->getCreditmemo());

        if (!$response->getIsSuccessful()) {
            Mage::throwException('Payment refund failed, please try again.');
        }

        if ($response->getTransactionId()) {
            $payment->setTransactionId($response->getTransactionId());
        }

        return $this;
    }

    /**
     * Get a Klarna order
     *
     * @param $order
     *
     * @return Klarna_Core_Model_Order
     */
    public function getKlarnaOrder($order)
    {
        if (!$this->hasData('klarna_order')) {
            $this->setData('klarna_order', Mage::getModel('klarna_core/order')->loadByOrder($order));
        }

        return $this->getData('klarna_order');
    }

    /**
     * Get api class
     *
     * @return Klarna_Core_Model_Api_PurchaseAbstract
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function getPurchaseApi()
    {
        if (!$this->hasData('purchase_api')) {
            $api = $this->_helper->getPurchaseApiInstance($this->_code, $this->getStore());
            $this->setData('purchase_api', $api);
        }

        return $this->getData('purchase_api');
    }

    /**
     * Get api class
     *
     * @return Klarna_Core_Model_Api_PostPurchaseAbstract
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function getPostPurchaseApi()
    {
        if (!$this->hasData('post_purchase_api')) {
            $api = $this->_helper->getPostPurchaseApiInstance($this->getStore());
            $this->setData('post_purchase_api', $api);
        }

        return $this->getData('post_purchase_api');
    }
}
