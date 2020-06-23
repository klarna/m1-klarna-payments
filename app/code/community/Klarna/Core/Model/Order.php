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
 * Klarna order used to associate a Klarna order with a Magento order
 *
 * @method Klarna_Core_Model_Order setKlarnaOrderId()
 * @method int getKlarnaOrderId()
 * @method Klarna_Core_Model_Order setSessionId()
 * @method string getSessionId()
 * @method Klarna_Core_Model_Order setReservationId()
 * @method string getReservationId()
 * @method Klarna_Core_Model_Order setOrderId()
 * @method int getOrderId()
 * @method Klarna_Core_Model_Order setIsAcknowledged(int $value)
 * @method int getIsAcknowledged()
 */
class Klarna_Core_Model_Order extends Mage_Core_Model_Abstract
{
    /**
     * Init
     */
    public function _construct()
    {
        $this->_init('klarna_core/order');
    }

    /**
     * Load by session id
     *
     * @param string $sessionId
     *
     * @return $this
     */
    public function loadBySessionId($sessionId)
    {
        return $this->load($sessionId, 'session_id');
    }

    /**
     * Load by an order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    public function loadByOrder(Mage_Sales_Model_Order $order)
    {
        return $this->load($order->getId(), 'order_id');
    }
}
