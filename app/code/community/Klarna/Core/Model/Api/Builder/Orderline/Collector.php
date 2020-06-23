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
 * Klarna total collector
 */
class Klarna_Core_Model_Api_Builder_Orderline_Collector
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
     * @return Klarna_Core_Model_Api_Builder_Orderline_Collector
     */
    protected function _initCollectors()
    {
        $checkoutType = Mage::helper('klarna_core')->getStoreApiTypeCode($this->_store);
        $totalsConfig = Mage::getConfig()->getNode(sprintf('klarna/order_lines/%s', $checkoutType));

        if (!$totalsConfig) {
            return $this;
        }

        foreach ($totalsConfig->children() as $totalCode => $totalConfig) {
            $class = $totalConfig->getClassName();
            if (!empty($class)) {
                $this->_collectors[$totalCode] = $this->_initModelInstance($class, $totalCode);
            }
        }

        return $this;
    }

    /**
     * Init model class by configuration
     *
     * @param string $class
     * @param string $totalCode
     *
     * @return Klarna_Core_Model_Api_Builder_Orderline_Collector
     */
    protected function _initModelInstance($class, $totalCode)
    {
        $model = Mage::getModel($class);
        if (!$model instanceof Klarna_Core_Model_Api_Builder_Orderline_Abstract) {
            Mage::throwException(
                Mage::helper('klarna_core')
                    ->__('The order item model should be extended from Klarna_Core_Model_Api_Builder_Orderline_Abstract.')
            );
        }

        $model->setCode($totalCode);

        return $model;
    }
}
