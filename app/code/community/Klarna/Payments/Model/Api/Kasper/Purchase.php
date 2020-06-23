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
 * Klarna payments kasper purchase class
 */
class Klarna_Payments_Model_Api_Kasper_Purchase extends Klarna_Core_Model_Api_PurchaseAbstract
{
    /**
     * @var string
     */
    protected $_builderType = 'klarna_payments/api_builder_kasper';

    /**
     * Create or update a session
     *
     * @param string $sessionId
     * @param bool   $createIfNotExists
     * @param bool   $updateAllowed
     *
     * @return Varien_Object
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function initKlarnaSession($sessionId = null, $createIfNotExists = false, $updateAllowed = false)
    {
        try {
            $api  = $this->_getPaymentsApi();
            $data = $this->getGeneratedCreateRequest();

            $klarnaOrder = null !== $sessionId ? $api->updateSession($sessionId, $data) : $api->createSession($data);

            // If existing order fails or is expired, create a new one
            if (!$klarnaOrder->getIsSuccessful()) {
                $data        = $this->getGeneratedCreateRequest();
                $klarnaOrder = $api->createSession($data);
            }
        } catch (Klarna_Payments_BuilderException $e) {
            Mage::log($e->getMessage(), Zend_Log::CRIT, 'klarna_payments_error.log');
            throw new Klarna_Core_Model_Api_Exception(
                $this->getHelper()
                ->__('Unable to initialize Klarna payments session')
            );
        }

        // If we still get an error, give up
        if (!$klarnaOrder->getIsSuccessful()) {
            throw new Klarna_Core_Model_Api_Exception(
                $this->getHelper()
                ->__('Unable to initialize Klarna payments session')
            );
        }

        $this->setKlarnaOrder($klarnaOrder);

        return $klarnaOrder;
    }

    /**
     * Place order
     *
     * @param string $id
     * @param array  $data
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function placeOrder($id = null, array $data = array())
    {
        return $this->_getPaymentsApi()->placeOrder($id, $data);
    }

    /**
     * Cancel authorization
     *
     * @param $id
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function cancelAuthorization($id)
    {
        return $this->_getPaymentsApi()->cancelAuthorization($id);
    }

    /**
     * Get generated create request
     *
     * @return array
     * @throws Klarna_Core_Exception
     */
    public function getGeneratedPlaceRequest()
    {
        return $this->getGenerator()
            ->setObject($this->getQuote())
            ->generateRequest(Klarna_Payments_Model_Api_Builder_Kasper::GENERATE_TYPE_PLACE)
            ->getRequest();
    }

    /**
     * Get generated client update request
     *
     * @return array
     * @throws Klarna_Core_Exception
     */
    public function getGeneratedClientUpdateRequest()
    {
        return $this->getGenerator()
            ->setObject($this->getQuote())
            ->generateRequest(Klarna_Payments_Model_Api_Builder_Kasper::GENERATE_TYPE_CLIENT_UPDATE)
            ->getRequest();
    }

    /**
     * @param string $sessionId
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response|string|void
     */
    public function readSession($sessionId)
    {
        $api = $this->_getPaymentsApi();
        return $api->readSession($sessionId);
    }

    /**
     * Get the api for payments api
     *
     * @return Klarna_Payments_Model_Api_Rest_Payments
     */
    protected function _getPaymentsApi()
    {
        return Mage::getSingleton('klarna_payments/api_rest_payments')
            ->setConfig($this->getConfig())
            ->setStore($this->getStore());
    }
}
