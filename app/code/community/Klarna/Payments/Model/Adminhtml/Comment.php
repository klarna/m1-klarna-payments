<?php
/**
 * This file is part of the Klarna Payments module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/**
 * Returning url for the merchant onboarding
 *
 * Class Klarna_Payments_Model_Adminhtml_Comment
 */
class Klarna_Payments_Model_Adminhtml_Comment
{

    /**
     * @return string
     */
    public function getCommentText()
    {
        $module = 'kp';
        $moduleVersion = Mage::getConfig()->getNode()->modules->Klarna_Payments->version;

        $platform = 'magento';
        $platformVersion = Mage::getVersion();

        $queryParameter = sprintf(
            '?plugin=%s&pluginVersion=%s&platform=%s&platformVersion=%s&products=%s',
            $module,
            $moduleVersion,
            $platform,
            $platformVersion,
            $module
        );

        $websiteId = null;
        $websiteCode = Mage::app()->getRequest()->getParam('website');
        if ($websiteCode) {
            $website = Mage::getModel('core/website')->load($websiteCode);
            $websiteId = $website->getId();
        }

        $country = Mage::app()->getWebsite($websiteId)->getConfig('general/store_information/merchant_country');
        if (empty($country)) {
            $country = Mage::app()->getWebsite()->getConfig('general/store_information/merchant_country');
        }

        if (!empty($country)) {
            $queryParameter .= '&country=' . $country;
        }

        $url = 'https://eu.portal.klarna.com/signup' . $queryParameter;
        if ($country === 'US') {
            $url = 'https://us.portal.klarna.com/signup' . $queryParameter;
        }

        $text = Mage::helper('klarna_payments')->__(
            'Click here to visit the Klarna Merchant Onboarding Page and request credentials.'
        );

        $html = '<a href="' . $url . '" target="_blank">' . $text . '</a>';
        return $html;
    }
}