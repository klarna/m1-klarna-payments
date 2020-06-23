<?php
/**
 * This file is part of the Klarna Core module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

class Klarna_Core_Block_Adminhtml_Sales_Order_View_Info extends Mage_Adminhtml_Block_Sales_Order_View_Info
{
    /**
     * Get link to edit order address page
     *
     * @param Mage_Sales_Model_Order_Address $address
     * @param string $label
     * @return string
     */
    public function getAddressEditLink($address, $label='')
    {
        if ($address->getOrder()->getPayment()->getMethod() == 'klarna_payments') {
            return '';
        }

        return parent::getAddressEditLink($address, $label);
    }
}