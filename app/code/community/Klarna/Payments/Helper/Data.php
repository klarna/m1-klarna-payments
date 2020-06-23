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
 * Klarna Payments data helper
 */
class Klarna_Payments_Helper_Data extends Mage_Payment_Helper_Data
{
    const MERCHANT_PORTAL_US = 'https://us.portal.klarna.com/orders/';
    const MERCHANT_PORTAL_EU = 'https://eu.portal.klarna.com/orders/';
    const MERCHANT_PORTAL_OC = 'https://oc.portal.klarna.com/orders/';

    /**
     * Check if a store allows data sharing
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return bool
     */
    public function getDataSharingEnabled($store = null)
    {
        return Mage::getSingleton('customer/session')->isLoggedIn() && Mage::getStoreConfigFlag('payment/klarna_payments/data_sharing', $store);
    }

    /**
     * get locale
     *
     * @param null $store
     *
     * @return string
     */
    public function getLocale($store = null)
    {
        $locale = Mage::getStoreConfig('general/locale/code', $store);
        if (!$locale) {
            return 'en_us';
        }

        return strtolower($locale);
    }

    public function getStoreMethods($store = null, $quote = null)
    {
        $res = parent::getStoreMethods($store, $quote);

        foreach ($res as $key => $payment) {
            if ($payment instanceof Klarna_Payments_Model_Payment_Payments) {
                $resKlarna = clone $payment;
                unset($res[$key]);

                /** @var Klarna_Payments_Model_Quote $klarnaQuote */
                $klarnaQuote = Mage::helper('klarna_payments/checkout')->getKlarnaQuote();
                foreach ($klarnaQuote->getPaymentMethodCategories() as $values) {

                    /** @var Klarna_Payments_Model_Payment_Payments $newRes */
                    $newRes = clone $resKlarna;
                    $newRes->setCategoryInformation($values);
                    $newRes->setCode('klarna_payments_' . $values['identifier']);
                    $newRes->setTitle($values['name']);

                    $res[] = $newRes;
                }

                break;
            }
        }

        usort($res, array($this, '_sortMethods'));
        return $res;
    }

    /**
     * get link to merchant portal for order
     *
     * @param $mageOrder
     * @param $klarnaOrder
     * @return string
     */
    public function getOrderMerchantPortalLink($mageOrder, $klarnaOrder)
    {
        $store = $mageOrder->getStore();

        $merchantId = Mage::getStoreConfig('klarna/api/merchant_id', $store);
        $apiVersion = Mage::getStoreConfig('klarna/api/api_version', $store);

        if ($apiVersion == 'na') {
            $url = self::MERCHANT_PORTAL_US;
        } elseif ($apiVersion == 'oc') {
            $url = self::MERCHANT_PORTAL_OC;
        } else {
            $url = self::MERCHANT_PORTAL_EU;
        }

        $merchantIdArray = explode("_", $merchantId);
        $url .= "merchants/" . $merchantIdArray[0] . "/orders/" . $klarnaOrder->getSessionId();
        return $url;
    }

    /**
     * Get klarna payment method name from quote
     *
     * @param string $indentifier
     * @param int $quoteId
     * @return string
     */
    public function getKlarnaPaymentNameByType($indentifier, $quoteId)
    {
        $paymentQuote = Mage::getModel('klarna_payments/quote')->loadActiveByQuoteId($quoteId);
        $paymentMethodCategories = $paymentQuote->getPaymentMethodCategories();
        if (!empty($paymentMethodCategories) && is_array($paymentMethodCategories)) {
            foreach ($paymentMethodCategories as $category) {
                if ($category['identifier'] == $indentifier) {
                    return $category['name'];
                }
            }
        }
        return ucwords(str_replace("_", " ", $indentifier));
    }
}
