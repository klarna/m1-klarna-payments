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
 * Klarna api integration interface for post purchase order management
 */
interface Klarna_Core_Model_Api_PostPurchaseApiInterface
{
    /**
     * Get Klarna Reservation Id
     *
     * @return string
     */
    public function getReservationId();

    /**
     * Capture an amount on an order
     *
     * @param string                         $orderId
     * @param float                          $amount
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function capture($orderId, $amount, $invoice = null);

    /**
     * Refund for an order
     *
     * @param string                            $orderId
     * @param float                             $amount
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function refund($orderId, $amount, $creditMemo = null);

    /**
     * Cancel an order
     *
     * @param string $orderId
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function cancel($orderId);

    /**
     * Release the authorization for an order
     *
     * @param string $orderId
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function release($orderId);

    /**
     * Acknowledge an order in order management
     *
     * @param string $orderId
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function acknowledgeOrder($orderId);

    /**
     * Update merchant references for a Klarna order
     *
     * @param string $orderId
     * @param string $reference1
     * @param string $reference2
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function updateMerchantReferences($orderId, $reference1, $reference2 = null);

    /**
     * Get the fraud status of an order to determine if it should be accepted or denied within Magento
     *
     * Return value of 1 means accept
     * Return value of 0 means still pending
     * Return value of -1 means deny
     *
     * @param string $orderId
     *
     * @return int
     */
    public function getFraudStatus($orderId);

    /**
     * Get order details from the api
     *
     * @param string $orderId
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function getOrder($orderId);
}
