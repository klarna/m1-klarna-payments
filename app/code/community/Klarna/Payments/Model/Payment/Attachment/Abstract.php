<?php
/**
 * This file is part of the Klarna Payments module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/**
 * Klarna attachments line abstract
 */
abstract class Klarna_Payments_Model_Payment_Attachment_Abstract
{
    /**
     * Order line code name
     *
     * @var string
     */
    protected $_code;


    /**
     * @var Klarna_Core_Model_Api_Builder_Abstract
     */
    protected $_object = null;


    /**
     * Set code name
     *
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->_code = $code;

        return $this;
    }

    /**
     * Retrieve code name
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Collect process.
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $object
     *
     * @return $this
     */
    public function collect($object)
    {
        $this->_setObject($object);

        return $this;
    }

    /**
     * Fetch
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $object
     *
     * @return $this
     */
    public function fetch($object)
    {
        $this->_setObject($object);

        return $this;
    }

    /**
     * Set the object which can be used inside attachments calculation
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $object
     *
     * @return $this
     */
    protected function _setObject($object)
    {
        $this->_object = $object;

        return $this;
    }

    /**
     * Get object
     *
     * @return Klarna_Core_Model_Api_Builder_Abstract
     */
    protected function _getObject()
    {
        if ($this->_object === null) {
            Mage::throwException(
                Mage::helper('klarna_payments')->__('Object model is not defined.')
            );
        }

        return $this->_object;
    }
}
