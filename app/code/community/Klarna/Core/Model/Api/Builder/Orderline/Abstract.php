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
 * Klarna order line abstract
 */
abstract class Klarna_Core_Model_Api_Builder_Orderline_Abstract
{
    /**
     * Order line code name
     *
     * @var string
     */
    protected $_code;

    /**
     * Order line is used to calculate a total
     *
     * For example, shipping total, order total, or discount total
     *
     * This should be set to false for items like order items
     *
     * @var bool
     */
    protected $_isTotalCollector = true;

    /**
     * @var Klarna_Core_Model_Api_Builder_Orderline_Abstract
     */
    protected $_object = null;

    /**
     * Check if the order line is for an order item or a total collector
     *
     * @return boolean
     */
    public function isIsTotalCollector()
    {
        return $this->_isTotalCollector;
    }

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
     * Set the object which can be used inside totals calculation
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
     * @return Klarna_Core_Model_Api_Builder_Orderline_Abstract
     */
    protected function _getObject()
    {
        if ($this->_object === null) {
            Mage::throwException(
                Mage::helper('klarna_core')->__('Object model is not defined.')
            );
        }

        return $this->_object;
    }

    /**
     * Get tax amount for discount
     *
     * @param $subtotal
     * @param $total
     * @return array
     */
    public function getTaxAmount($subtotal, $total)
    {
        $valueInclTax = $subtotal->getValueInclTax();
        $valueExclTax = $subtotal->getValueExclTax();
        if ($valueExclTax == 0 || $valueInclTax == 0) {
            return 0;
        }

        $taxRate = ($valueInclTax / $valueExclTax);
        return -($total->getValue() - ($total->getValue() / $taxRate));
    }

    /**
     * Get the tax rate for the discount order line
     *
     * @param $checkout
     *
     * @return float
     */
    public function getDiscountTaxRate($checkout)
    {
        $discountTaxRate = false;

        if ($checkout->getItems()) {
            $itemTaxRates = array();
            $totalsIncludingTax = array();
            $totalsExcludingTax = array();
            foreach ($checkout->getItems() as $item) {
                $totalsIncludingTax[] = $item['total_amount'];
                $totalsExcludingTax[] = $item['total_amount'] - $item['total_tax_amount'];
                $itemTaxRates[] = isset($item['tax_rate']) ? ($item['tax_rate'] * 1) : 0;
            }

            $itemTaxRates = array_unique($itemTaxRates);
            $taxRateCount = count($itemTaxRates);

            if (1 < $taxRateCount) {
                $discountTaxRate = ((array_sum($totalsIncludingTax) / array_sum($totalsExcludingTax)) - 1) * 100;
                $discountTaxRate = Mage::helper('klarna_core')->toApiFloat($discountTaxRate);
            } elseif (1 === $taxRateCount) {
                $discountTaxRate = reset($itemTaxRates);
            }
        }

        return $discountTaxRate === false ? $checkout->getDiscountTaxRate() : $discountTaxRate;
    }
}
