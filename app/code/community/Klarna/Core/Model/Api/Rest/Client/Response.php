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
 * Response from the API
 *
 * @method Klarna_Core_Model_Api_Rest_Client_Response getRequest()
 * @method Klarna_Core_Model_Api_Rest_Client_Response setResponseObject($string)
 * @method Klarna_Core_Model_Api_Rest_Client_Response setIsSuccessful(bool $flag)
 * @method bool getIsSuccessful()
 * @method Zend_Http_Response getResponseObject()
 */
class Klarna_Core_Model_Api_Rest_Client_Response extends Klarna_Core_Model_Api_Response
{
    /**
     * Model class name
     */
    const RESPONSE_TYPE = 'klarna_core/api_rest_client_response';

    /**
     * Get the error message to display for invalid items.
     *
     * @return string
     */
    public function getDefaultErrorMessage()
    {
        return (null !== $this->getRequest()) ? $this->getRequest()->getData('default_error_message')
            : 'Unknown error';
    }

    /**
     * Set the raw response array from the API call
     *
     * @param array $response
     *
     * @return $this
     */
    public function setResponse(array $response)
    {
        // Remove first node for a response for one item
        $keys = array_keys($response);
        if (1 === count($keys) && 0 === $keys[0]) {
            $response = $response[0];
        }

        $idField = (null !== $this->getRequest()) ? $this->getRequest()->getData('id_field') : null;
        $emptyResponse = (empty($response) && $this->getResponseObject()->getStatus() !== 204);
        if (null !== $idField && ($emptyResponse  || isset($response['error_code']))) {
            $id        = $this->getRequest()->getIds();
            $_response = array(
                'error'         => true,
                'error_message' => $this->getDefaultErrorMessage(),
                'is_successful' => false,
            );

            if (null !== $idField) {
                $_response[$idField] = $id ?: null;
            }

            $response = array_merge($response, $_response);
        }

        $this->addData($response);

        return $this;
    }

    /**
     * Set the request used to load the data
     *
     * @param Klarna_Core_Model_Api_Rest_Client_Request $request
     *
     * @return $this
     */
    public function setRequest(Klarna_Core_Model_Api_Rest_Client_Request $request)
    {
        $this->setIdFieldName($request->getData('id_field'));
        $this->setData('request', $request);

        return $this;
    }
}
