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
 * Klarna core observer methods
 */
class Klarna_Core_Model_Observer
{
    /**
     * Generate item list for payment capture
     *
     * @param Varien_Event_Observer $observer
     */
    public function prepareCapture(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getPayment();

        if ($payment->getMethodInstance() instanceof Klarna_Core_Model_Payment_Method_Abstract) {
            $payment->setInvoice($observer->getInvoice());
        }
    }
}
