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
 * Customer group config source
 */
class Klarna_Core_Model_System_Config_Source_Customergroup
{
    /**
     * Customer groups options array
     *
     * @var array
     */
    protected $_options;

    /**
     * Retrieve customer groups as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (null === $this->_options) {
            $this->_options = Mage::getResourceModel('customer/group_collection')->toOptionArray();
            array_unshift($this->_options, array('value' => -1, 'label' => ''));
        }

        return $this->_options;
    }
}
