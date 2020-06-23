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
 * Generate shipping order line details
 */
class Klarna_Core_Model_Api_Builder_Orderline_Shipping extends Klarna_Core_Model_Api_Builder_Orderline_Abstract
{
    const ITEM_TYPE_SHIPPING = 'shipping_fee';

    /**
     * Collect totals process.
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $checkout
     *
     * @return $this
     */
    public function collect($checkout)
    {
        $object = $checkout->getObject();
        $helper = Mage::helper('klarna_core');

        if ($object instanceof Mage_Sales_Model_Quote) {
            $totals = $object->getTotals();
            if (isset($totals['shipping'])) {
                /** @var Mage_Sales_Model_Quote_Address_Total_Shipping $total */
                $total   = $totals['shipping'];
                $address = $total->getAddress();
                $amount  = $address->getBaseShippingAmount();

                if ($helper->getSeparateTaxLine($object->getStore())) {
                    $unitPrice = $amount;
                    $taxRate   = 0;
                    $taxAmount = 0;
                } else {
                    $taxRate   = $this->_calculateShippingTax($object);
                    $taxAmount = $address->getShippingTaxAmount() + $address->getShippingHiddenTaxAmount();
                    $unitPrice = $amount + $taxAmount;
                }

                $checkout->addData(
                    array(
                    'shipping_unit_price'   => $helper->toApiFloat($unitPrice),
                    'shipping_tax_rate'     => $helper->toApiFloat($taxRate),
                    'shipping_total_amount' => $helper->toApiFloat($unitPrice),
                    'shipping_tax_amount'   => $helper->toApiFloat($taxAmount),
                    'shipping_title'        => $total->getTitle(),
                    'shipping_reference'    => $total->getCode()

                    )
                );
            }
        }

        if ($object instanceof Mage_Sales_Model_Order_Invoice || $object instanceof Mage_Sales_Model_Order_Creditmemo) {
            $unitPrice = $object->getBaseShippingInclTax();
            $taxRate   = $this->_calculateShippingTax($object);
            $taxAmount = $object->getShippingTaxAmount() + $object->getShippingHiddenTaxAmount();

            $checkout->addData(
                array(
                'shipping_unit_price'   => $helper->toApiFloat($unitPrice),
                'shipping_tax_rate'     => $helper->toApiFloat($taxRate),
                'shipping_total_amount' => $helper->toApiFloat($unitPrice),
                'shipping_tax_amount'   => $helper->toApiFloat($taxAmount),
                'shipping_title'        => 'Shipping',
                'shipping_reference'    => 'shipping'

                )
            );
        }

        return $this;
    }

    /**
     * Add order details to checkout request
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $checkout
     *
     * @return $this
     */
    public function fetch($checkout)
    {
        if ($checkout->getShippingTotalAmount()) {
            $checkout->addOrderLine(
                array(
                'type'             => self::ITEM_TYPE_SHIPPING,
                'reference'        => $checkout->getShippingReference(),
                'name'             => $checkout->getShippingTitle(),
                'quantity'         => 1,
                'unit_price'       => $checkout->getShippingUnitPrice(),
                'tax_rate'         => $checkout->getShippingTaxRate(),
                'total_amount'     => $checkout->getShippingTotalAmount(),
                'total_tax_amount' => $checkout->getShippingTaxAmount(),
                )
            );
        }

        return $this;
    }

    /**
     * Calculate shipping tax rate for an object
     *
     * @param Mage_Sales_Model_Quote|Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $object
     *
     * @return float
     */
    protected function _calculateShippingTax($object)
    {
        $store     = $object->getStore();
        $taxCalc   = Mage::getModel('tax/calculation');
        $request   = $taxCalc->getRateRequest($object->getShippingAddress(), $object->getBillingAddress(), $object->getCustomerTaxClassId(), $store);
        $taxRateId = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $store);

        return $taxCalc->getRate($request->setProductClassId($taxRateId));
    }
}
