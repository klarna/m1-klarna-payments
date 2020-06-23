<?php
/**
 * This file is part of the Klarna Payments module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

class Klarna_Payments_Model_Payment_Attachment_Default extends Klarna_Payments_Model_Payment_Attachment_Abstract
{

    /**
     * @param Klarna_Core_Model_Api_Builder_Abstract $payment
     *
     * @return $this
     */
    public function collect($payment)
    {
        return $this;
    }


    /**
     * @param Klarna_Core_Model_Api_Builder_Abstract $payment
     *
     * @return $this
     */
    public function fetch($payment)
    {
        return $this;
    }

}