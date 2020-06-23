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
 * Klarna order resource
 */
class Klarna_Core_Model_Resource_Order extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Init
     */
    public function _construct()
    {
        $this->_init('klarna_core/order', 'klarna_order_id');
    }
}
