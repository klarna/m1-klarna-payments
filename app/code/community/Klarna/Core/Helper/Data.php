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
 * Klarna Core helper
 */
class Klarna_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Klarna node paths
     */
    const XPATH_API_VERSIONS            = 'klarna/api_versions';
    const XPATH_API_TYPES               = 'klarna/api_types';
    const XPATH_API_PURCHASE_TYPES      = 'klarna/api_purchase_types';
    const XPATH_API_POST_PURCHASE_TYPES = 'klarna/api_post_purchase_types';

    /**
     * API types
     */
    const API_TYPE_PURCHASE      = 'purchase';
    const API_TYPE_POST_PURCHASE = 'post_purchase';

    /**
     * Cache variables
     */
    protected $_cacheApiTypes;
    protected $_cacheApiVersions;
    protected $_cachePurchaseApiTypes;
    protected $_cachePostPurchaseApiTypes;
    protected $_cacheStoreApiVersion = array();

    /**
     * Determine if current store supports the use of partial captures and refunds
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return bool
     */
    public function getPartialPaymentSupport($store = null)
    {
        return !(bool)$this->getStoreApiVersionOptions($store)->getPartialPaymentDisabled();
    }

    /**
     * Determine if current store supports delayed push notifications
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return bool
     */
    public function getDelayedPushNotification($store = null)
    {
        return !(bool)$this->getStoreApiVersionOptions($store)->getDelayedPushNotification();
    }

    /**
     * Determine if current store supports separate tax lines
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return bool
     */
    public function getSeparateTaxLine($store = null)
    {
        return (bool)$this->getStoreApiVersionOptions($store)->getSeparateTaxLine();
    }

    /**
     * Determine if FPT is set to be included in the subtotal
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return bool
     */
    public function getDisplayInSubtotalFPT($store = null)
    {
        return Mage::getStoreConfigFlag('tax/weee/include_in_subtotal', $store);
    }


    /**
     * Determine if product price excludes VAT or not
     *
     * @param Store  $store
     * @return bool
     */
    public function getPriceExcludesVat($store = null)
    {
        return !Mage::getStoreConfigFlag('tax/calculation/price_includes_tax', $store);
    }

    /**
     * Determine if tax is applied before or after discount
     *
     * @param Store  $store
     * @return bool
     */
    public function getTaxBeforeDiscount($store = null)
    {
        return Mage::getStoreConfigFlag('tax/calculation/apply_after_discount', $store);
    }

    /**
     * Get the checkout type code for a store
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return string
     */
    public function getStoreApiTypeCode($store = null)
    {
        return $this->getStoreApiVersionConfig($store)->getType();
    }

    /**
     * Get configuration parameters for a store
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return Varien_Object
     * @throws Klarna_Core_Exception
     */
    public function getStoreApiVersionConfig($store = null)
    {
        $version = Mage::getStoreConfig('klarna/api/api_version', $store);

        if (null === $version) {
            $store = Mage::app()->getStore($store);

            throw new Klarna_Core_Exception(sprintf('Api version not set for store %s', $store->getFrontendName()));
        }

        if (!isset($this->_cacheStoreApiVersion[$version])) {
            $this->_cacheStoreApiVersion[$version] = $this->getApiVersion($version);
        }

        return $this->_cacheStoreApiVersion[$version];
    }

    /**
     * Get configuration options for a store
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return Varien_Object
     */
    public function getStoreApiVersionOptions($store = null)
    {
        $versionConfig = $this->getStoreApiVersionConfig($store);

        return $versionConfig->hasOptions() ? $versionConfig->getOptions() : new Varien_Object();
    }

    /**
     * Get post purchase api instance
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return Klarna_Core_Model_Api_PostPurchaseAbstract
     * @throws Klarna_Core_Model_Api_Exception
     * @throws Mage_Core_Exception
     */
    public function getPostPurchaseApiInstance($store = null)
    {
        if (!$this->getPostPurchaseEnabled($store)) {
            throw new Mage_Core_Exception('Klarna order management disabled.');
        }

        $code                = Mage::getStoreConfig('klarna/api/post_purchase_api', $store);
        $versionConfig       = $this->getStoreApiVersionConfig($store);
        $postPurchaseApiType = $this->getPostPurchaseApiType($code);
        $instance            = Mage::getSingleton($postPurchaseApiType->getClass());

        if (!$instance) {
            throw new Klarna_Core_Model_Api_Exception(sprintf('Cannot initiate api type "%s"!', $postPurchaseApiType->getClass()));
        }

        $instance->setStore($store);
        $instance->setConfig($versionConfig);

        return $instance;
    }

    /**
     * Check if post purchase is enabled by store
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return bool
     */
    public function getPostPurchaseEnabled($store = null)
    {
        return Mage::getStoreConfigFlag('klarna/api/post_purchase_api', $store);
    }

    /**
     * Get purchase api instance for a payment type
     *
     * @param string                $paymentMethodCode
     * @param Mage_Core_Model_Store $store
     *
     * @return Klarna_Core_Model_Api_PurchaseAbstract
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function getPurchaseApiInstance($paymentMethodCode, $store = null)
    {
        $versionConfig = $this->getStoreApiVersionConfig($store);

        $purchaseApiType = $this->getPurchaseApiType($paymentMethodCode);
        $instance        = Mage::getSingleton($purchaseApiType->getClass());

        if (!$instance) {
            throw new Klarna_Core_Model_Api_Exception(sprintf('Cannot initiate api type "%s"!', $purchaseApiType->getClass()));
        }

        $instance->setStore($store);
        $instance->setConfig($versionConfig);

        return $instance;
    }

    /**
     * Get the details for an api type
     *
     * A null code type returns all api Types
     *
     * @param string|null $code
     *
     * @return array|Varien_Object
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function getApiType($code = null)
    {
        if (null === $this->_cacheApiTypes) {
            $types = $this->_getXpathAsArray(self::XPATH_API_TYPES);
            foreach ($types as &$configObject) {
                Mage::dispatchEvent(
                    'klarna_load_api_config', array(
                    'options' => $configObject
                    )
                );
            }

            $this->_cacheApiTypes = $types;
        }

        if (null !== $code) {
            if (!isset($this->_cacheApiTypes[$code])) {
                throw new Klarna_Core_Model_Api_Exception(sprintf('Api type "%s" is invalid!', $code));
            }

            return $this->_cacheApiTypes[$code];
        }

        return $this->_cacheApiTypes;
    }

    /**
     * Get the details for an api version
     *
     * A null code type returns all api versions
     *
     * @param string|null $code
     *
     * @return Varien_Object
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function getApiVersion($code = null)
    {
        if (null === $this->_cacheApiVersions) {
            $versions = $this->_getXpathAsArray(self::XPATH_API_VERSIONS);

            foreach ($versions as &$configObject) {
                $options = $configObject->hasOptions() ? $configObject->getOptions() : array();

                $apiTypeObject  = $this->getApiType($configObject->getType());
                $apiTypeOptions = $apiTypeObject->hasOptions() ? $apiTypeObject->getOptions() : array();

                $options       = array_merge($apiTypeOptions, $options);
                $optionsObject = new Varien_Object($options);

                $configObject->setOptions($optionsObject);

                Mage::dispatchEvent(
                    'klarna_load_version_details', array(
                    'options' => $configObject
                    )
                );
            }

            $this->_cacheApiVersions = $versions;
        }

        if (null !== $code) {
            if (!isset($this->_cacheApiVersions[$code])) {
                throw new Klarna_Core_Model_Api_Exception(sprintf('Api version "%s" is invalid!', $code));
            }

            return $this->_cacheApiVersions[$code];
        }

        return $this->_cacheApiVersions;
    }

    /**
     * Get the details for a purchase api type
     *
     * A null code type returns all purchase api types
     *
     * @param string|null $code
     *
     * @return array|mixed
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function getPurchaseApiType($code = null)
    {
        if (null === $this->_cachePurchaseApiTypes) {
            $this->_cachePurchaseApiTypes = $this->_getXpathAsArray(self::XPATH_API_PURCHASE_TYPES);
        }

        if (null !== $code) {
            if (!isset($this->_cachePurchaseApiTypes[$code])) {
                throw new Klarna_Core_Model_Api_Exception(sprintf('Api purchase api type "%s" is invalid!', $code));
            }

            return $this->_cachePurchaseApiTypes[$code];
        }

        return $this->_cachePurchaseApiTypes;
    }

    /**
     * Get the details for a post purchase api type
     *
     * A null code type returns all post purchase api types
     *
     * @param string|null $code
     *
     * @return array|mixed
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function getPostPurchaseApiType($code = null)
    {
        if (null === $this->_cachePostPurchaseApiTypes) {
            $this->_cachePostPurchaseApiTypes = $this->_getXpathAsArray(self::XPATH_API_POST_PURCHASE_TYPES);
        }

        if (null !== $code) {
            if (!isset($this->_cachePostPurchaseApiTypes[$code])) {
                throw new Klarna_Core_Model_Api_Exception(sprintf('Api post purchase api type "%s" is invalid!', $code));
            }

            return $this->_cachePostPurchaseApiTypes[$code];
        }

        return $this->_cachePostPurchaseApiTypes;
    }

    /**
     * Get an xml path as an array of Varien_Objects
     *
     * @param $xpath
     *
     * @return array
     */
    protected function _getXpathAsArray($xpath)
    {
        $array = array();

        if ($typeConfig = Mage::getConfig()->getNode($xpath)) {
            foreach ($typeConfig->children() as $code => $details) {
                $config = $details->asArray();
                unset($config['@']);
                $config['code'] = $code;
                $array[$code]   = new Varien_Object($config);
            }
        }

        return $array;
    }

    /**
     * Prepare float for API call
     *
     * @param float $float
     *
     * @return int
     */
    public function toApiFloat($float)
    {
        return round($float * 100);
    }
}
