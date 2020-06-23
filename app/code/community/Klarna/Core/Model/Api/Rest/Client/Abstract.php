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
 * Abstract class for rest resource integration
 *
 * @method Klarna_Core_Model_Api_Rest_Client_Abstract setStore(Mage_Core_Model_Store $store)
 * @method Klarna_Core_Model_Api_Rest_Client_Abstract setConfig(Varien_Object $config)
 * @method Varien_Object getConfig()
 */
abstract class Klarna_Core_Model_Api_Rest_Client_Abstract extends Varien_Object
{
    /**
     * Get current client store
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        if (!$this->hasStore()) {
            $this->setData('store', Mage::app()->getStore());
        }

        return $this->getData('store');
    }

    /**
     * Get rest client
     *
     * @return Klarna_Core_Model_Api_Rest_Client
     */
    public function getRestClient()
    {
        return Mage::getSingleton(
            'klarna_core/api_rest_client', array(
            'config'        => $this->_getRequestClientConfig(),
            'log_file_name' => 'klarna_rest_request.log'
            )
        );
    }

    /**
     * Get a new request object for building a request
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Request
     */
    public function getNewRequestObject()
    {
        return $this->getRestClient()->getNewRequestObject();
    }

    /**
     * Perform a request
     *
     * @param Klarna_Core_Model_Api_Rest_Client_Request $request
     *
     * @throws Klarna_Core_Model_Api_Exception
     * @return Klarna_Core_Model_Api_Rest_Client_Response|string
     */
    public function request($request)
    {
        return $this->getRestClient()
            ->request($request);
    }

    /**
     * Get resource id from Location URL
     *
     * This assumes the ID is the last url path
     *
     * @param string|Klarna_Core_Model_Api_Rest_Client_Response $location
     *
     * @return string
     */
    public function getLocationResourceId($location)
    {
        if ($location instanceof Klarna_Core_Model_Api_Rest_Client_Response) {
            $location = $location->getResponseObject()->getHeader('Location');
        }

        $location      = rtrim($location, '/');
        $locationArray = explode('/', $location);

        return array_pop($locationArray);
    }

    /**
     * Get request configuration
     *
     * @return Varien_Object
     */
    protected function _getRequestClientConfig()
    {
        $baseUrl = Mage::getStoreConfigFlag('klarna/api/test_mode', $this->getStore())
            ? $this->getConfig()->getTestdriveUrl()
            : $this->getConfig()->getProductionUrl();

        $config = new Varien_Object(
            array(
            'auth_username' => Mage::getStoreConfig('klarna/api/merchant_id', $this->getStore()),
            'auth_password' => Mage::getStoreConfig('klarna/api/shared_secret', $this->getStore()),
            'base_url'      => $baseUrl,
            'debug'         => Mage::getStoreConfigFlag('klarna/api/debug', $this->getStore())
            )
        );

        Mage::dispatchEvent(
            'klarna_core_get_request_config', array(
            'request_configuration' => $config
            )
        );

        return $config;
    }
}
