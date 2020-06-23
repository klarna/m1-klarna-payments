<?php
/**
 * This file is part of the Klarna OrderManagement module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

class Klarna_OrderManagement_Model_Observer
{
    /**
     * Update User-Agent with module version info
     *
     * @param $event
     */
    public function klarnaCoreClientUserAgentString($event)
    {
        $version = Mage::getConfig()->getModuleConfig('Klarna_OrderManagement')->version;
        $versionObj = $event->getVersionStringObject();
        $verString = $versionObj->getVersionString();
        $verString .= ";Klarna_OM_v{$version}";
        $versionObj->setVersionString($verString);
    }
}