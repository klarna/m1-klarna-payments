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
 * Klarna versions config source
 */
class Klarna_Core_Model_System_Config_Source_Version
{
    /**
     * Get version details
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper  = Mage::helper('klarna_core');
        $options = array();

        if ($versions = $helper->getApiVersion()) {
            foreach ($versions as $version) {
                $options[] = array(
                    'label' => Mage::helper('klarna_core')->__($version->getLabel()),
                    'value' => $version->getCode()
                );
            }
        } else {
            $options[] = array(
                'label' => Mage::helper('klarna_core')->__('No API Versions Available'),
                'value' => null
            );
        }

        return $options;
    }
}
