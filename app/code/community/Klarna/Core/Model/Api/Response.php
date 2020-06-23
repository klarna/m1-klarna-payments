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
 * Response object from a remote request
 *
 * @method bool getIsSuccessful()
 * @method Klarna_Core_Model_Api_Response setIsSuccessful($boolean)
 * @method string getTransactionId()
 * @method Klarna_Core_Model_Api_Response setTransactionId($string)
 */
class Klarna_Core_Model_Api_Response extends Varien_Object
{
    /**
     * Build the default values for the object
     */
    protected function _construct()
    {
        $this->setData(
            array(
            'is_successful' => false
            )
        );
    }
}
