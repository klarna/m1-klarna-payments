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
 * Klarna notifications controller
 */
class Klarna_Core_NotificationController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Klarna_Core_Helper_Data
     */
    protected $_helper;

    /**
     * Controller construct
     */
    public function _construct()
    {
        $this->_helper = Mage::helper('klarna_core');
    }

    /**
     * Klarna notification
     */
    public function indexAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
            $body = new Varien_Object(Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody()));
        } catch (Exception $e) {
            $this->_sendBadRequestResponse();

            return;
        }

        if (!($sessionId = $this->getRequest()->getParam('id'))) {
            $sessionId = $body->getOrderId();
        }

        try {
            $klarnaOrder = Mage::getModel('klarna_core/order')->loadBySessionId($sessionId);
            $order       = Mage::getModel('sales/order')->load($klarnaOrder->getOrderId());

            if (!$order->getId()) {
                throw new Klarna_Core_Exception('Order not found');
            }

            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = $order->getPayment();

            if ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
                || $order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING
            ) {
                switch ($body->getEventType()) {
                    case Klarna_Core_Model_Api_ApiTypeAbstract::ORDER_NOTIFICATION_FRAUD_REJECTED:
                        $payment->setNotificationResult(true);
                        $payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
                        break;
                    case Klarna_Core_Model_Api_ApiTypeAbstract::ORDER_NOTIFICATION_FRAUD_STOPPED:
                        $order->addStatusHistoryComment(
                            $this->_helper->__('Suspected Fraud: DO NOT SHIP. If already shipped, ' .
                        'please attempt to stop the carrier from delivering.')
                        );
                        $payment->setNotificationResult(true);
                        $payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
                        break;
                    case Klarna_Core_Model_Api_ApiTypeAbstract::ORDER_NOTIFICATION_FRAUD_ACCEPTED:
                        $payment->setNotificationResult(true);
                        $payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_ACCEPT, false);
                        break;
                }

                $statusObject = new Varien_Object(
                    array(
                    'status' => $payment->getMethodInstance()->getConfigData('order_status')
                    )
                );

                Mage::dispatchEvent(
                    'klarna_push_notification_before_set_state', array(
                    'order'         => $order,
                    'klarna_order'  => $klarnaOrder,
                    'status_object' => $statusObject
                    )
                );

                if (Mage_Sales_Model_Order::STATE_PROCESSING == $order->getState()) {
                    $order->addStatusHistoryComment(
                        $this->_helper->__('Order processed by Klarna.'),
                        $statusObject->getStatus()
                    );
                }

                $order->save();
            } elseif (Klarna_Core_Model_Api_ApiTypeAbstract::ORDER_NOTIFICATION_FRAUD_REJECTED == $body->getEventType()
                && $order->getState() != Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
            ) {
                $payment->setNotificationResult(false);
                $payment->setIsFraudDetected(true);
                $payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
                $order->save();
            }
        } catch (Klarna_Core_Exception $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody(
                Mage::helper('core')->jsonEncode(
                    array(
                    'error'   => 400,
                    'message' => $e->getMessage(),
                    )
                )
            );
            Mage::logException($e);
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            Mage::logException($e);
        }
    }

    /**
     * API call to notify Magento that the order is now ready to receive order management calls
     */
    public function pushAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $order              = null;
        $sessionId          = $this->getRequest()->getParam('id');
        $responseCodeObject = new Varien_Object(
            array(
            'response_code' => 200
            )
        );

        try {
            $klarnaOrder = Mage::getModel('klarna_core/order')->loadBySessionId($sessionId);
            $order       = Mage::getModel('sales/order')->load($klarnaOrder->getOrderId());

            if (!$order->getId()) {
                throw new Klarna_Core_Exception('Order not found');
            }

            $store = $order->getStore();

            Mage::dispatchEvent(
                'klarna_push_notification_before', array(
                'order'                => $order,
                'klarna_order_id'      => $sessionId,
                'response_code_object' => $responseCodeObject,
                )
            );

            // Add comment to order and update status if still in payment review
            if ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                /** @var Mage_Sales_Model_Order_Payment $payment */
                $payment = $order->getPayment();
                $payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_UPDATE, true);

                $statusObject = new Varien_Object(
                    array(
                    'status' => $payment->getMethodInstance()->getConfigData('order_status')
                    )
                );

                Mage::dispatchEvent(
                    'klarna_push_notification_before_set_state', array(
                    'order'         => $order,
                    'klarna_order'  => $klarnaOrder,
                    'status_object' => $statusObject
                    )
                );

                if (Mage_Sales_Model_Order::STATE_PROCESSING == $order->getState()) {
                    $order->addStatusHistoryComment(
                        $this->_helper->__('Order processed by Klarna.'),
                        $statusObject->getStatus()
                    );
                }
            }

            $checkoutType = $this->_helper->getStoreApiTypeCode($store);
            Mage::dispatchEvent(
                "klarna_push_notification_after_type_{$checkoutType}", array(
                'order'                => $order,
                'klarna_order'         => $klarnaOrder,
                'response_code_object' => $responseCodeObject,
                )
            );

            Mage::dispatchEvent(
                'klarna_push_notification_after', array(
                'order'                => $order,
                'klarna_order'         => $klarnaOrder,
                'response_code_object' => $responseCodeObject,
                )
            );

            try {
                $api = $this->_helper->getPostPurchaseApiInstance($store);

                // Update order references
                $api->updateMerchantReferences($sessionId, $order->getIncrementId());

                // Acknowledge order
                if ($order->getState() != Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW
                    && !$klarnaOrder->getIsAcknowledged()
                ) {
                    $api->acknowledgeOrder($sessionId);
                    $order->addStatusHistoryComment('Acknowledged request sent to Klarna');
                    $klarnaOrder->setIsAcknowledged(1);
                    $klarnaOrder->save();
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }

            // Cancel order in Klarna if cancelled on store
            if ($order->isCanceled()) {
                $this->_cancelFailedOrder($klarnaOrder->getReservationId(), $order->getStore());
            }

            $order->save();
        } catch (Klarna_Core_Exception $e) {
            $responseCodeObject->setResponseCode(500);
            $cancelObject = new Varien_Object(
                array(
                'cancel_order' => true
                )
            );
            Mage::dispatchEvent(
                'klarna_push_notification_order_not_found_cancel_before', array(
                'session_id'           => $sessionId,
                'cancel_object'        => $cancelObject,
                'response_code_object' => $responseCodeObject,
                'controller_action'    => $this
                )
            );

            Mage::dispatchEvent(
                'klarna_push_notification_order_not_found_cancel', array(
                'session_id'           => $sessionId,
                'cancel_object'        => $cancelObject,
                'response_code_object' => $responseCodeObject,
                'controller_action'    => $this
                )
            );
        } catch (Exception $e) {
            $responseCodeObject->setResponseCode(500);
            Mage::dispatchEvent(
                'klarna_push_notification_failed', array(
                'order'                => $order,
                'klarna_order_id'      => $sessionId,
                'response_code_object' => $responseCodeObject,
                'controller_action'    => $this
                )
            );
            Mage::logException($e);
        }

        $this->getResponse()->setHttpResponseCode($responseCodeObject->getResponseCode());
    }

    /**
     * Cancel a failed order in Klarna
     *
     * @param string                $reservationId
     * @param Mage_Core_Model_Store $store
     *
     * @return $this
     */
    protected function _cancelFailedOrder($reservationId, $store = null)
    {
        if (null === $reservationId) {
            return $this;
        }

        try {
            /**
             * This will only cancel orders already available in order management.
             * Orders not yet available for cancellation will be cancelled on the push or will expire
             */
            $this->_helper->getPostPurchaseApiInstance($store)->cancel($reservationId);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Send bad request response header
     *
     * @param array|string|null $message
     *
     * @throws Zend_Controller_Response_Exception
     */
    protected function _sendBadRequestResponse($message = null)
    {
        if (null === $message) {
            $message = $this->_helper->__('Bad request');
        }

        if (is_array($message)) {
            $message = implode('\n', $message);
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setHttpResponseCode(400);
        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array(
                'error_type' => $message
                )
            )
        );
    }
}
