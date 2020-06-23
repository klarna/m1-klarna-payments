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
 * Klarna api integration abstract
 *
 * @method Klarna_Core_Model_Api_ApiTypeAbstract setStore(Mage_Core_Model_Store $store)
 * @method Mage_Core_Model_Store getStore()
 * @method Klarna_Core_Model_Api_ApiTypeAbstract setConfig(Varien_Object $config)
 * @method Varien_Object getConfig()
 * @method Klarna_Core_Model_Api_ApiTypeAbstract setQuote(Mage_Sales_Model_Quote $quote)
 */
class Klarna_Core_Model_Api_ApiTypeAbstract extends Varien_Object
{
    /**
     * Order statuses
     */
    const ORDER_STATUS_AUTHORIZED    = 'AUTHORIZED';
    const ORDER_STATUS_PART_CAPTURED = 'PART_CAPTURED';
    const ORDER_STATUS_CAPTURED      = 'CAPTURED';
    const ORDER_STATUS_CANCELLED     = 'CANCELLED';
    const ORDER_STATUS_EXPIRED       = 'EXPIRED';
    const ORDER_STATUS_CLOSED        = 'CLOSED';

    /**
     * Order fraud statuses
     */
    const ORDER_FRAUD_STATUS_ACCEPTED = 'ACCEPTED';
    const ORDER_FRAUD_STATUS_REJECTED = 'REJECTED';
    const ORDER_FRAUD_STATUS_PENDING  = 'PENDING';

    /**
     * Order notification statuses
     */
    const ORDER_NOTIFICATION_FRAUD_REJECTED = 'FRAUD_RISK_REJECTED';
    const ORDER_NOTIFICATION_FRAUD_ACCEPTED = 'FRAUD_RISK_ACCEPTED';
    const ORDER_NOTIFICATION_FRAUD_STOPPED  = 'FRAUD_RISK_STOPPED';

    /**
     * @var Varien_Object
     */
    protected $_klarnaOrder = null;

    /**
     * API type code
     *
     * @var string
     */
    protected $_builderType = '';

    /**
     * Get Klarna Checkout Reservation Id
     *
     * @return string
     */
    public function getReservationId()
    {
        return $this->getKlarnaOrder()->getId();
    }

    /**
     * Get generated create request
     *
     * @return array
     * @throws Klarna_Core_Exception
     */
    public function getGeneratedCreateRequest()
    {
        return $this->getGenerator()
            ->setObject($this->getQuote())
            ->generateRequest(Klarna_Core_Model_Api_Builder_Abstract::GENERATE_TYPE_CREATE)
            ->getRequest();
    }

    /**
     * Get generated update request
     *
     * @return array
     * @throws Klarna_Core_Exception
     */
    public function getGeneratedUpdateRequest()
    {
        return $this->getGenerator()
            ->setObject($this->getQuote())
            ->generateRequest(Klarna_Core_Model_Api_Builder_Abstract::GENERATE_TYPE_UPDATE)
            ->getRequest();
    }

    /**
     * Get builder type
     *
     * @return string
     */
    protected function _getBuilderType()
    {
        return $this->_builderType;
    }

    /**
     * Get request generator
     *
     * @return Klarna_Core_Model_Api_Builder_Abstract
     * @throws Klarna_Core_Exception
     */
    public function getGenerator()
    {
        $generator = Mage::getModel($this->_getBuilderType());

        if (!$generator) {
            throw new Klarna_Core_Exception('Invalid api generator type code.');
        }

        return $generator;
    }

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
    public function getFraudStatus($orderId)
    {
        return 1;
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->hasData('quote')) {
            return $this->getData('quote');
        }

        return $this->_getQuote();
    }

    /**
     * Set Klarna checkout order details
     *
     * @param Varien_Object $klarnaOrder
     *
     * @return $this
     */
    public function setKlarnaOrder(Varien_Object $klarnaOrder)
    {
        $this->_klarnaOrder = $klarnaOrder;

        return $this;
    }

    /**
     * Get Klarna checkout order details
     *
     * @return Varien_Object
     */
    public function getKlarnaOrder()
    {
        if (null === $this->_klarnaOrder) {
            $this->_klarnaOrder = new Varien_Object();
        }

        return $this->_klarnaOrder;
    }

    /**
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Get Klarna checkout helper
     *
     * @return Klarna_Core_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('klarna_core');
    }
}
