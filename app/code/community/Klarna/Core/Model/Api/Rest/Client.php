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
 * Klarna REST api client
 *
 * @method Klarna_Core_Model_Api_Rest_Client setRequest(Klarna_Core_Model_Api_Rest_Client_Request $request)
 * @method Klarna_Core_Model_Api_Rest_Client_Request getRequest()
 * @method Klarna_Core_Model_Api_Rest_Client setResponseType($string)
 * @method string getResponseType()
 * @method Klarna_Core_Model_Api_Rest_Client setConfig(Varien_Object $config)
 * @method Varien_Object getConfig()
 * @method Klarna_Core_Model_Api_Rest_Client setLogFileName($string)
 * @method string getLogFileName()
 * @method Klarna_Core_Model_Api_Rest_Client setDebug(bool $flag)
 * @method Klarna_Core_Model_Api_Rest_Client setMethod($string)
 * @method string getMethod()
 * @method Klarna_Core_Model_Api_Rest_Client setAuthUsername($string)
 * @method Klarna_Core_Model_Api_Rest_Client setAuthPassword($string)
 * @method Klarna_Core_Model_Api_Rest_Client setBaseUrl($string)
 */
class Klarna_Core_Model_Api_Rest_Client extends Varien_Object
{
    /**
     * Current open client connection
     *
     * @var Zend_Http_Client
     */
    protected $_client;

    /**
     * Stores the parameters sent.
     *
     * @var array
     */
    protected $_parameters = array();

    /**
     * Request method used for get
     *
     * @var string
     */
    const REQUEST_METHOD_GET = Zend_Http_Client::GET;

    /**
     * Request method used for post
     *
     * @var string
     */
    const REQUEST_METHOD_POST = Zend_Http_Client::POST;

    /**
     * Request method for delete
     *
     * @var string
     */
    const REQUEST_METHOD_DELETE = Zend_Http_Client::DELETE;

    /**
     * Request method used for patch
     *
     * @var string
     */
    const REQUEST_METHOD_PATCH = 'PATCH';

    /**
     * Response type for RAW data
     *
     * @var string
     */
    const RAW_RESPONSE_TYPE = 'raw';

    /**
     * JSON encoding type string
     *
     * @var string
     */
    const ENC_JSON = 'application/json';

    /**
     * Default request object type
     *
     * @var string
     */
    protected $_requestObject = 'klarna_core/api_rest_client_request';

    /**
     * Response of last request
     *
     * @var Varien_Object|mixed
     */
    protected $_response = null;

    /**
     * Response object model from type
     *
     * @var Varien_Object
     */
    protected $_responseObject = null;

    /**
     * Default values for the request configuration.
     *
     * @var array
     */
    protected $_requestConfig = array(
        'maxredirects'    => 5,
        'strictredirects' => false,
        'useragent'       => 'Magento_Rest_Client',
        'timeout'         => 30,
        'adapter'         => 'Zend_Http_Client_Adapter_Socket',
        'httpversion'     => Zend_Http_Client::HTTP_1,
        'keepalive'       => true,
        'storeresponse'   => true,
        'strict'          => true,
        'output_stream'   => false,
        'encodecookies'   => true,
        'rfc3986_strict'  => false
    );

    /**
     * Init connection client
     *
     * @return $this
     */
    protected function _construct()
    {
        $version             = Mage::getConfig()->getModuleConfig('Klarna_Core')->version;
        $mageVersion = Mage::getVersion();
        $mageEdition = Mage::getEdition();

        $versionStringObject = new Varien_Object(
            array(
            'version_string' => "Klarna_Core_v{$version}"
            )
        );
        Mage::dispatchEvent(
            'klarna_core_client_user_agent_string', array(
            'version_string_object' => $versionStringObject
            )
        );
        $this->setRequestConfig('useragent', $versionStringObject->getVersionString() . " (Magento {$mageEdition} {$mageVersion})");
        $this->_client = $this->getClient();

        return $this;
    }

    /**
     * Get rest client auth username
     *
     * @return string
     */
    public function getAuthUsername()
    {
        if (!$this->hasData('auth_username')) {
            $this->setAuthUsername($this->getConfig()->getAuthUsername());
        }

        return $this->getData('auth_username');
    }

    /**
     * Get rest client auth password
     *
     * @return string
     */
    public function getAuthPassword()
    {
        if (!$this->hasData('auth_password')) {
            $this->setAuthPassword($this->getConfig()->getAuthPassword());
        }

        return $this->getData('auth_password');
    }

    /**
     * Get base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if (!$this->hasData('base_url')) {
            $this->setBaseUrl($this->getConfig()->getBaseUrl());
        }

        return $this->getData('base_url');
    }

    /**
     * Get debug setting
     *
     * @return bool
     */
    public function getDebug()
    {
        if (!$this->hasData('debug')) {
            $this->setDebug((bool)$this->getConfig()->getDebug());
        }

        return (bool)$this->getData('debug');
    }

    /**
     * Load the connection client
     *
     * @param null $url
     *
     * @return Zend_Http_Client
     */
    public function getClient($url = null)
    {
        if (null === $this->_client) {
            $client = new Zend_Http_Client(null, $this->getRequestConfig());

            $client->setHeaders(
                array(
                'Accept-encoding' => 'gzip,deflate',
                'accept'          => 'application/json',
                'content-type'    => 'application/json'
                )
            );

            if ($this->getAuthUsername()) {
                $client->setAuth($this->getAuthUsername(), $this->getAuthPassword());
            }

            $this->_client = $client;
        }

        if (null !== $url) {
            try {
                if (!is_string($url) || null === parse_url($url, PHP_URL_SCHEME)) {
                    if (!is_array($url)) {
                        $url = array($url);
                    }

                    $urlBase = $this->getBaseUrl();
                    $urlBase = rtrim($urlBase, '/');

                    array_unshift($url, $urlBase);

                    $url = implode('/', $url);
                }

                $url = preg_replace('/\s+/', '', $url);

                $this->_client->setUri($url);
            } catch (Zend_Uri_Exception $e) {
                $this->_debug($e, Zend_Log::CRIT);
            }
        }

        return $this->_client;
    }

    /**
     * Reset client connection
     *
     * @return $this
     */
    public function resetClient()
    {
        $this->_client = null;

        return $this;
    }

    /**
     * Covert response into response object
     *
     * @throws Klarna_Core_Model_Api_Exception
     * @return mixed
     */
    protected function _getResponse()
    {
        if (null === $this->_response) {
            $responseArray = array();

            /** @var Zend_Http_Response $response */
            try {
                $response = $this->getLastResponse();
            } catch (Zend_Http_Client_Exception $e) {
                $this->_debug($e, Zend_Log::CRIT);
            }

            if (self::RAW_RESPONSE_TYPE === $this->getResponseType()) {
                if ($response instanceof Zend_Http_Response) {
                    $response = $response->getBody();
                }

                $this->_response = $response;

                return $this->_response;
            } elseif ($response !== false) {
                try {
                    $_responseArray = Mage::helper('core')->jsonDecode($response->getBody());
                    if ($_responseArray) {
                        $responseArray = $_responseArray;
                    }
                } catch (Exception $e) {
                }
            }

            $this->_response = $this->_getResponseObject()
                ->setRequest($this->getData('request'))
                ->setResponseObject($response)
                ->setIsSuccessful($response->isSuccessful())
                ->setResponse($responseArray);
        }

        return $this->_response;
    }

    /**
     * Get the response type object
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response|Varien_Object
     * @throws Klarna_Core_Model_Api_Exception
     */
    protected function _getResponseObject()
    {
        if (null === $this->_responseObject) {
            if (self::RAW_RESPONSE_TYPE == $this->getResponseType()) {
                throw new Klarna_Core_Model_Api_Exception('No response object available for raw response type.');
            }

            $responseModel = Mage::getModel($this->getData('response_type'));

            if (!$responseModel) {
                throw new Klarna_Core_Model_Api_Exception('Invalid response type.');
            }

            $this->_responseObject = $responseModel;
        }

        return $this->_responseObject;
    }

    /**
     * Do a request by method
     *
     * @param string $method
     * @param string $url
     *
     * @return mixed
     */
    protected function _requestByMethod($method, $url)
    {
        $this->getClient($url);

        $this->_methodRequest($method);

        $this->_response = null;

        $response = $this->_getResponse();
        $request  = $this->getRequest();

        Mage::dispatchEvent("klarna_core_rest_{$request->getFullActionName()}_{$method}_after", $this->_getEventData());
        Mage::dispatchEvent("klarna_core_rest_request_{$method}_after", $this->_getEventData());
        Mage::dispatchEvent("klarna_core_rest_request_after", $this->_getEventData());

        return $response;
    }

    /**
     * Perform the request
     *
     * @param string $method
     *
     * @throws Exception
     * @return Zend_Http_Response
     */
    protected function _methodRequest($method = self::REQUEST_METHOD_GET)
    {
        /** @var Klarna_Core_Model_Api_Rest_Client_Request $request */
        $request = $this->getRequest();
        $client  = $this->getClient();

        // Set GET params
        $paramsGet = $request->getParams(
            self::REQUEST_METHOD_GET,
            Klarna_Core_Model_Api_Rest_Client_Request::REQUEST_PARAMS_FORMAT_TYPE_ARRAY
        );
        if (!empty($paramsGet)) {
            $client->setParameterGet($paramsGet);
        }

        // Set POST & PATCH params
        $paramsPost = $request->getParams(
            array(
            self::REQUEST_METHOD_POST,
            self::REQUEST_METHOD_PATCH
            )
        );
        if (!empty($paramsPost)) {
            if ($request->getPostJson()) {
                $client->setRawData($paramsPost, self::ENC_JSON);
            } else {
                $client->setParameterPost($paramsPost);
            }
        }

        // Set METHOD Type params (global params)
        $paramsGlobal = $request->getParams(
            false,
            Klarna_Core_Model_Api_Rest_Client_Request::REQUEST_PARAMS_FORMAT_TYPE_ARRAY
        );
        if (!empty($paramsGlobal)) {
            switch ($method) {
                case self::REQUEST_METHOD_POST:
                    if ($request->getPostJson()) {
                        $client->setRawData($paramsGlobal, self::ENC_JSON);
                    } else {
                        $client->setParameterPost($paramsGlobal);
                    }
                    break;
                case self::REQUEST_METHOD_GET:
                default:
                    $client->setParameterGet($paramsGlobal);
            }
        }

        // Do the request
        try {
            Mage::dispatchEvent("klarna_core_rest_{$request->getFullActionName()}_{$method}_before", $this->_getEventData());
            Mage::dispatchEvent("klarna_core_rest_request_{$method}_before", $this->_getEventData());
            Mage::dispatchEvent("klarna_core_rest_request_before", $this->_getEventData());

            $response = $client->request($method);

            if ($this->getRequest()->getFollowLocationHeader() && $response->isSuccessful()
                && ($location = $response->getHeader('Location'))
            ) {
                $this->_debug($client->getLastResponse(), Zend_Log::DEBUG);
                $this->_debug('Following Location header', Zend_Log::DEBUG);

                $client   = $this->getClient($location);
                $response = $client->request(self::REQUEST_METHOD_GET);
            }
        } catch (Exception $e) {
            $this->_debug($e, Zend_Log::CRIT);
            $code = $e->getCode();

            if (5 !== floor($code / 100)) {
                $code = 500;
            }

            $response = new Zend_Http_Response($code, array(), $e->getMessage(), '1.1', $e->getMessage());
        }

        $this->_debug($client->getLastRequest(), Zend_Log::DEBUG);
        $this->_debug($client->getLastResponse(), Zend_Log::DEBUG);

        $this->setData('last_response', $response);

        $timeout = $request->getRequestTimeout();
        if (null !== $timeout) {
            $oldTimeout = $this->getData('_temp_timeout', $this->getRequestConfig('timeout'));
            $this->setRequestConfig('timeout', $oldTimeout);
            $this->getClient()->setConfig($this->getRequestConfig());
        }

        return $response;
    }

    /**
     * Perform a request
     *
     * @param Klarna_Core_Model_Api_Rest_Client_Request $request
     *
     * @throws Klarna_Core_Model_Api_Exception
     * @return Klarna_Core_Model_Api_Rest_Client_Response|string
     */
    public function request(Klarna_Core_Model_Api_Rest_Client_Request $request)
    {
        $timeout = $request->getRequestTimeout();
        if (null !== $timeout) {
            $this->setData('_temp_timeout', $this->getRequestConfig('timeout'));
            $this->setRequestConfig('timeout', $timeout);
            $this->getClient()->setConfig($this->getRequestConfig());
        }

        $this->setData('request', $request);
        $this->setData('response_type', $request->getData('response_type'));

        $method = strtoupper(trim($request->getMethod()));

        $this->setMethod($method);

        return $this->_requestByMethod($this->getMethod(), $request->getUrl());
    }

    /**
     * Get a new request object for building a request
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Request
     */
    public function getNewRequestObject()
    {
        return Mage::getModel($this->_requestObject);
    }

    /**
     * The request configuration used for the request.
     * If a field type is provided then the raw data will be returned. Otherwise, the data will be formatted to be used
     * for the HTTP request.
     *
     * @see self::_getRequestConfig()
     *
     * @param string $name
     *
     * @return array
     */
    public function getRequestConfig($name = null)
    {
        if (null === $name) {
            return $this->_getRequestConfig();
        } else {
            if (isset($this->_requestConfig[$name])) {
                return $this->_requestConfig[$name];
            }
        }

        return null;
    }

    /**
     * Prepares the request configuration array to be used in the HTTP request.
     *
     * @return array
     */
    protected function _getRequestConfig()
    {
        $_requestConfigCurrent = $this->_requestConfig;
        $_requestConfigNew     = array();
        foreach ($_requestConfigCurrent as $name => $value) {
            if (!empty($value)) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $_requestConfigNew[$name] = $value;
            }
        }

        return $_requestConfigNew;
    }

    /**
     * Set the configuration for sending a request.
     *
     * @param array|string $name
     * @param mixed        $value
     *
     * @return $this
     */
    public function setRequestConfig($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->_requestConfig[$k] = $v;
            }
        } else {
            $this->_requestConfig[$name] = $value;
        }

        return $this;
    }

    /**
     * Get the last response from the API
     *
     * @return mixed|Zend_Http_Response
     */
    public function getLastResponse()
    {
        $lastResponse = $this->getData('last_response');

        if ($lastResponse instanceof Zend_Http_Response) {
            return $lastResponse;
        }

        if (!empty($lastResponse)) {
            return Zend_Http_Response::fromString($lastResponse);
        }

        if (null === $lastResponse) {
            return false;
        }

        return $lastResponse;
    }

    /**
     * Log debug messages
     *
     * @param $message
     * @param $level
     */
    protected function _debug($message, $level)
    {
        if (Zend_Log::DEBUG != $level || $this->getDebug()) {
            Mage::log($this->_rawDebugMessage($message), $level, $this->getLogFileName(), true);
        }
    }

    /**
     * Raw debug message for logging
     *
     * @param $message
     *
     * @return string
     */
    protected function _rawDebugMessage($message)
    {
        if ($message instanceof Zend_Http_Client) {
            $client  = $message;
            $message = $client->getLastRequest();

            if ($response = $client->getLastResponse()) {
                $message .= "\n\n" . $response->asString();
            }
        } elseif ($message instanceof Zend_Http_Response) {
            $message = $message->getHeadersAsString(true, "\n") . "\n" . $message->getBody();
        } elseif ($message instanceof Exception) {
            $message = $message->__toString();
        }

        return $message;
    }

    /**
     * Get array of objects transferred to default events processing
     *
     * @return array
     */
    protected function _getEventData()
    {
        $request = $this->getRequest();
        $client  = $this->getClient();

        $eventData = array(
            'request'     => $request,
            'raw_request' => $client->getLastRequest(),
        );

        if ($this->getLastResponse()) {
            $eventData = array_merge(
                $eventData, array(
                'response'     => $this->_getResponse(),
                'raw_response' => $client->getLastResponse()
                )
            );
        }

        return $eventData;
    }
}
