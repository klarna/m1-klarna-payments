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
 * Klarna multi-select backend model to remove empty values
 */
class Klarna_Core_Model_System_Config_Backend_Multiselect extends Mage_Core_Model_Config_Data
{
    /**
     * Before saving of multi select to remove empty values
     *
     * @return mixed
     */
    public function _beforeSave()
    {
        if ($this->getValue() == -1) {
            $this->setValue(null);
        }

        return parent::_beforeSave();
    }
}
