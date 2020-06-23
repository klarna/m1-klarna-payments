<?php
/**
 * This file is part of the Klarna OrderManagement module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/**
 * Api model for the Klarna order management
 */
class Klarna_OrderManagement_Model_Api_Rest_Ordermanagement extends Klarna_Core_Model_Api_Rest_Client_Abstract
{
    /**
     * Used by merchants to acknowledge the order.
     *
     * Merchants will receive the order confirmation push until the order has been acknowledged.
     *
     * @param $id
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function acknowledgeOrder($id)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'acknowledge'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to acknowledge order.');

        return $this->request($request);
    }

    /**
     * Get the current state of an order
     *
     * @param $id
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function getOrder($id)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setIdField('order_id')
            ->setDefaultErrorMessage('Error: Order not found.');

        return $this->request($request);
    }

    /**
     * Update the total order amount of an order, subject to a new customer credit check.
     *
     * The updated amount can optionally be accompanied by a descriptive text and new order lines. Supplied order lines will
     * replace the existing order lines. If no order lines are supplied in the call, the existing order lines will be
     * deleted. The updated 'order_amount' must not be negative, nor less than current 'captured_amount'. Currency is
     * inferred from the original order.
     *
     * @param string $id
     * @param array  $data
     *
     * @return \Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function updateOrderItems($id, $data)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'authorization'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_PATCH)
            ->setParams($data)
            ->setDefaultErrorMessage('Error: Unable to acknowledge order.');

        return $this->request($request);
    }

    /**
     * Extend the order's authorization by default period according to merchant contract.
     *
     * @param $id
     *
     * @return \Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function extendAuthorization($id)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'extend-authorization-time'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to extend order authorization.');

        return $this->request($request);
    }

    /**
     * Update one or both merchant references. To clear a reference, set its value to "" (empty string).
     *
     * @param string $id
     * @param string $merchantReference1
     * @param string $merchantReference2
     *
     * @return \Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function updateMerchantReferences($id, $merchantReference1, $merchantReference2 = null)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'merchant-references'
        );

        $data = array(
            'merchant_reference1' => $merchantReference1
        );

        if (null !== $merchantReference2) {
            $data['merchant_reference2'] = $merchantReference2;
        }

        Mage::dispatchEvent(
            'klarna_rest_merchant_reference_update', array(
            'order_id' => $id,
            'data'     => $data
            )
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_PATCH)
            ->setParams($data)
            ->setDefaultErrorMessage('Error: Unable to update merchant references order.');

        return $this->request($request);
    }

    /**
     * Update billing and/or shipping address for an order, subject to customer credit check.
     * Fields can be updated independently. To clear a field, set its value to "" (empty string).
     *
     * Mandatory fields can not be cleared
     *
     * @param string $id
     * @param array  $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function updateAddresses($id, $data)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'customer-details'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_PATCH)
            ->setParams($data)
            ->setDefaultErrorMessage('Error: Unable to acknowledge order.');

        return $this->request($request);
    }

    /**
     * Cancel an authorized order. For a cancellation to be successful, there must be no captures on the order.
     * The authorized amount will be released and no further updates to the order will be allowed.
     *
     * @param $id
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function cancelOrder($id)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'cancel'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to cancel order.');

        return $this->request($request);
    }

    /**
     * Capture the supplied amount. Use this call when fulfillment is completed, e.g. physical goods are being shipped to
     * the customer.
     * 'captured_amount' must be equal to or less than the order's 'remaining_authorized_amount'.
     * Shipping address is inherited from the order. Use PATCH method below to update the shipping address of an individual
     * capture. The capture amount can optionally be accompanied by a descriptive text and order lines for the captured
     * items.
     *
     * @param $id
     * @param $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function captureOrder($id, $data)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'captures'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setParams($data)
            ->setDefaultErrorMessage('Error: Unable to capture order.');

        return $this->request($request);
    }

    /**
     * Retrieve a capture
     *
     * @param $id
     * @param $captureId
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function getCapture($id, $captureId)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'captures',
            $captureId
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setDefaultErrorMessage('Error: Unable to get capture.');

        return $this->request($request);
    }

    /**
     * Appends new shipping info to a capture.
     *
     * @param $id
     * @param $captureId
     * @param $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function addShippingDetailsToCapture($id, $captureId, $data)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'captures',
            $captureId,
            'shipping-info'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setParams($data)
            ->setDefaultErrorMessage('Error: Unable to add shipping detail to capture.');

        return $this->request($request);
    }

    /**
     * Update the billing address for a capture. Shipping address can not be updated.
     * Fields can be updated independently. To clear a field, set its value to "" (empty string).
     *
     * Mandatory fields can not be cleared,
     *
     * @param $id
     * @param $captureId
     * @param $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function updateCaptureBillingAddress($id, $captureId, $data)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'captures',
            $captureId,
            'customer-details'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_PATCH)
            ->setParams($data)
            ->setDefaultErrorMessage('Error: Unable to update order address.');

        return $this->request($request);
    }

    /**
     * Trigger a new send out of customer communication., typically a new invoice, for a capture.
     *
     * @param $id
     * @param $captureId
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function resendOrderInvoice($id, $captureId)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'captures',
            $captureId,
            'trigger-send-out'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to trigger order send out.');

        return $this->request($request);
    }

    /**
     * Refund an amount of a captured order. The refunded amount will be credited to the customer.
     * The refunded amount must not be higher than 'captured_amount'.
     * The refunded amount can optionally be accompanied by a descriptive text and order lines.
     *
     * @param $id
     * @param $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function refund($id, $data)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'refunds'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setParams($data)
            ->setDefaultErrorMessage('Error: Unable to issue order refund.');

        return $this->request($request);
    }

    /**
     * Signal that there is no intention to perform further captures.
     *
     * @param $id
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function releaseAuthorization($id)
    {
        $url = array(
            'ordermanagement',
            'v1',
            'orders',
            $id,
            'release-remaining-authorization'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to acknowledge order.');

        return $this->request($request);
    }
}
