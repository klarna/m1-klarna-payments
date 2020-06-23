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
 * Generate order line details for gift card
 */
class Klarna_Core_Model_Api_Builder_Orderline_Giftcard extends Klarna_Core_Model_Api_Builder_Orderline_Abstract
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

        if (is_array($totals) && isset($totals['giftcardaccount'])) {
            $total  = $totals['giftcardaccount'];
            $helper = Mage::helper('klarna_core');
            $value  = $helper->toApiFloat($total->getValue());

            $checkout->addData(
                array(
                'giftcardaccount_unit_price'   => $value,
                'giftcardaccount_tax_rate'     => 0,
                'giftcardaccount_total_amount' => $value,
                'giftcardaccount_tax_amount'   => 0,
                'giftcardaccount_title'        => $total->getTitle(),
                'giftcardaccount_reference'    => $total->getCode()

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
        if ($checkout->getGiftcardaccountTotalAmount()) {
            $checkout->addOrderLine(
                array(
                'type'             => Klarna_Core_Model_Api_Builder_Orderline_Discount::ITEM_TYPE_DISCOUNT,
                'reference'        => $checkout->getGiftcardaccountReference(),
                'name'             => $checkout->getGiftcardaccountTitle(),
                'quantity'         => 1,
                'unit_price'       => $checkout->getGiftcardaccountUnitPrice(),
                'tax_rate'         => $checkout->getGiftcardaccountTaxRate(),
                'total_amount'     => $checkout->getGiftcardaccountTotalAmount(),
                'total_tax_amount' => $checkout->getGiftcardaccountTaxAmount(),
                )
            );
        }

        return $this;
    }
}
