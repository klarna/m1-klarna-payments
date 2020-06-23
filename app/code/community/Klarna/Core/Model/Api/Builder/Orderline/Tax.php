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
 * Generate tax order line details
 */
class Klarna_Core_Model_Api_Builder_Orderline_Tax extends Klarna_Core_Model_Api_Builder_Orderline_Abstract
{
    const ITEM_TYPE_TAX = 'sales_tax';

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

        if (!$helper->getSeparateTaxLine($object->getStore())) {
            return $this;
        }

        if ($checkout->getObject() instanceof Mage_Sales_Model_Quote) {
            $totalTax = $object->isVirtual() ? $object->getBillingAddress()->getBaseTaxAmount()
                : $object->getShippingAddress()->getBaseTaxAmount();
        } else {
            $totalTax = $object->getBaseTaxAmount();
        }

        $checkout->addData(
            array(
            'tax_unit_price'   => $helper->toApiFloat($totalTax),
            'tax_total_amount' => $helper->toApiFloat($totalTax)

            )
        );

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
        $helper = Mage::helper('klarna_core');

        if ($checkout->getTaxUnitPrice()) {
            $checkout->addOrderLine(
                array(
                'type'             => self::ITEM_TYPE_TAX,
                'reference'        => $helper->__('Sales Tax'),
                'name'             => $helper->__('Sales Tax'),
                'quantity'         => 1,
                'unit_price'       => $checkout->getTaxUnitPrice(),
                'tax_rate'         => 0,
                'total_amount'     => $checkout->getTaxTotalAmount(),
                'total_tax_amount' => 0,
                )
            );
        }

        return $this;
    }
}
