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
 * Generate order line details for customer balance
 */
class Klarna_Core_Model_Api_Builder_Orderline_Customerbalance extends Klarna_Core_Model_Api_Builder_Orderline_Abstract
{
    /**
     * Collect totals process.
     *
     * @param Klarna_Kco_Model_Api_Builder_Abstract $checkout
     *
     * @return $this
     */
    public function collect($checkout)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote  = $checkout->getObject();
        $totals = $quote->getTotals();

        if (is_array($totals) && isset($totals['customerbalance'])) {
            $total  = $totals['customerbalance'];
            $helper = Mage::helper('klarna_core');
            $value  = $helper->toApiFloat($total->getValue());

            $checkout->addData(
                array(
                'customerbalance_unit_price'   => $value,
                'customerbalance_tax_rate'     => 0,
                'customerbalance_total_amount' => $value,
                'customerbalance_tax_amount'   => 0,
                'customerbalance_title'        => $total->getTitle(),
                'customerbalance_reference'    => $total->getCode()

                )
            );
        }

        return $this;
    }

    /**
     * Add grand total information to address
     *
     * @param Klarna_Kco_Model_Api_Builder_Abstract $checkout
     *
     * @return $this
     */
    public function fetch($checkout)
    {
        if ($checkout->getCustomerbalanceTotalAmount()) {
            $checkout->addOrderLine(
                array(
                'type'             => Klarna_Core_Model_Api_Builder_Orderline_Discount::ITEM_TYPE_DISCOUNT,
                'reference'        => $checkout->getCustomerbalanceReference(),
                'name'             => $checkout->getCustomerbalanceTitle(),
                'quantity'         => 1,
                'unit_price'       => $checkout->getCustomerbalanceUnitPrice(),
                'tax_rate'         => $checkout->getCustomerbalanceTaxRate(),
                'total_amount'     => $checkout->getCustomerbalanceTotalAmount(),
                'total_tax_amount' => $checkout->getCustomerbalanceTaxAmount(),
                )
            );
        }

        return $this;
    }
}
