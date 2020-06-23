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
 * Klarna payments attachment collector
 */
class Klarna_Payments_Model_Payment_Attachment_Collector
{
    /**
     * Corresponding store object
     *
     * @var Mage_Core_Model_Store
     */
    protected $_store;

    /**
     * Sorted models
     *
     * @var array
     */
    protected $_collectors = array();

    /**
     * Init corresponding models
     *
     * @param array $options
     */
    public function __construct($options)
    {
        if (isset($options['store'])) {
            $this->_store = $options['store'];
        } else {
            $this->_store = Mage::app()->getStore();
        }

        $this->_initCollectors();
    }

    /**
     * Get models for calculation logic
     *
     * @return array
     */
    public function getCollectors()
    {
        return $this->_collectors;
    }

    /**
     * Initialize models configuration and objects
     *
     * @return Klarna_Payments_Model_Payment_Attachment_Collector
     *
     * @throws Mage_Core_Exception
     */
    protected function _initCollectors()
    {
        $attachmentConfig = Mage::getConfig()->getNode('klarna/attachments');

        if (!$attachmentConfig) {
            return $this;
        }

        foreach ($attachmentConfig->children() as $attachmentCode => $attachmentConfig) {
            $class = $attachmentConfig->getClassName();
            if (!empty($class)) {
                $this->_collectors[$attachmentCode] = $this->_initModelInstance($class, $attachmentCode);
            }
        }

        return $this;
    }


    /**
     * Init model class by configuration
     *
     * @param $class
     *
     * @param $totalCode
     *
     * @return false|Klarna_Payments_Model_Payment_Attachment_Abstract
     *
     * @throws Mage_Core_Exception
     */
    protected function _initModelInstance($class, $totalCode)
    {
        $model = Mage::getModel($class);
        if (!$model instanceof Klarna_Payments_Model_Payment_Attachment_Abstract) {
            Mage::throwException(
                Mage::helper('klarna_payments')
                    ->__('The order item model should be extended from Klarna_Payments_Model_Payment_Attachment_Abstract.')
            );
        }
        $model->setCode($totalCode);
        return $model;
    }
}
