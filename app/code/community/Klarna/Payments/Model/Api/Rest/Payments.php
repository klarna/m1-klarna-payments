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
 * Klarna Payments rest integration with Klarna
 */
class Klarna_Payments_Model_Api_Rest_Payments extends Klarna_Core_Model_Api_Rest_Client_Abstract
{
    /**
     * Create new session
     *
     * @param array $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     */
    public function createSession(array $data)
    {
        $url = array(
            'payments',
            'v1',
            'sessions'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setIdField('session_id')
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to create session.')
            ->setParams($data);

        return $this->request($request);
    }

    /**
     * Update session
     *
     * @param string $id
     * @param array  $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function updateSession($id = null, array $data = array())
    {
        if (null === $id) {
            throw new Klarna_Core_Model_Api_Exception('Klarna session id required for update');
        }

        $url = array(
            'payments',
            'v1',
            'sessions',
            $id
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setIdField('session_id')
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to update session.')
            ->setParams($data);

        return $this->request($request);
    }

    /**
     * @param string $id
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response|string
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function readSession($id)
    {
        if (null === $id) {
            throw new Klarna_Core_Model_Api_Exception('Klarna session id required for the read method');
        }

        $url = array(
            'payments',
            'v1',
            'sessions',
            $id
        );

        $request = $this->getNewRequestObject()->setUrl($url);

        return $this->request($request);
    }

    /**
     * Place order
     *
     * @param string $id
     * @param array  $data
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function placeOrder($id = null, array $data = array())
    {
        if (null === $id) {
            throw new Klarna_Core_Model_Api_Exception('Klarna token id required to place order');
        }

        $url = array(
            'payments',
            'v1',
            'authorizations',
            $id,
            'order'
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setIdField('order_id')
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_POST)
            ->setDefaultErrorMessage('Error: Unable to create order.')
            ->setParams($data);

        return $this->request($request);
    }

    /**
     * Cancel order authorization
     *
     * @param string $id
     *
     * @return Klarna_Core_Model_Api_Rest_Client_Response
     * @throws Klarna_Core_Model_Api_Exception
     */
    public function cancelAuthorization($id)
    {
        if (null === $id) {
            throw new Klarna_Core_Model_Api_Exception('Klarna token id required to cancel authorization');
        }

        $url = array(
            'payments',
            'v1',
            'authorizations',
            $id
        );

        $request = $this->getNewRequestObject()
            ->setUrl($url)
            ->setIdField('order_id')
            ->setMethod(Klarna_Core_Model_Api_Rest_Client::REQUEST_METHOD_DELETE)
            ->setDefaultErrorMessage('Error: Unable to cancel authorization.');

        return $this->request($request);
    }
}
