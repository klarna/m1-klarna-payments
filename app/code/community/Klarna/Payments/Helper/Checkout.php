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
 * Klarna Payments checkout helper
 */
class Klarna_Payments_Helper_Checkout extends Mage_Core_Helper_Abstract
{
    /**
     * @var Klarna_Payments_Model_Quote
     */
    protected $_klarnaQuote = null;

    /**
     * @var Varien_Object
     */
    protected $_klarnaPayments = null;

    /**
     * @var bool
     */
    protected $_inQuoteUpdate = false;

    /**
     * @var Varien_Object
     */
    protected $_requestHashes = null;

    /**
     * Hash check values
     */
    const HASH_CHECK_ITEMS_CHANGED = 'items_changed';
    const HASH_CHECK_CHANGED       = 'change';
    const HASH_CHECK_NO_CHANGE     = 'no_change';

    /**
     * Get checkout design config value
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return mixed
     */
    public function getCheckoutDesignConfig($store = null)
    {
        $designOptions = Mage::getStoreConfig('checkout/klarna_payments_design', $store);

        return is_array($designOptions) ? $designOptions : array();
    }

    /**
     * Cancel the authorization token for a Klarna Payments Quote
     *
     * @param Klarna_Payments_Model_Quote $klarnaQuote
     *
     * @return bool
     */
    public function cancelKlarnaQuoteAuthorizationToken(Klarna_Payments_Model_Quote $klarnaQuote)
    {
        $result = false;
        if ($klarnaQuote->getAuthorizationToken()) {
            $response = Mage::helper('klarna_core')->getPurchaseApiInstance('klarna_payments')
                ->cancelAuthorization($klarnaQuote->getAuthorizationToken());

            if (!$response->getIsSuccessful()) {
                Mage::logException(
                    new Klarna_Payments_Exception(
                        sprintf(
                            'Unable to cancel authorization token %s for quote #%d',
                            $klarnaQuote->getAuthorizationToken(),
                            $klarnaQuote->getQuoteId()
                        )
                    )
                );
            } else {
                $result = true;
            }

            $klarnaQuote->setAuthorizationToken(null);
            $klarnaQuote->save();
        }

        return $result;
    }

    /**
     * @return Varien_Object
     */
    public function createCheckHashes()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $createRequest   = $this->getPurchaseApiInstance()->getGeneratedCreateRequest();
        $itemsToCheck    = array(
            'purchase_country', 'purchase_currency', 'locale', 'order_amount', 'order_tax_amount', 'order_lines',
            'billing_address', 'shipping_address'
        );
        $checkItems      = array();
        foreach ($itemsToCheck as $item) {
            if (isset($createRequest[$item])) {
                $checkItems[$item] = $createRequest[$item];
            }
        }

        $orderItemRequestToken = hash('sha256', $checkoutSession->getQuoteId() . ':' . Zend_Json::encode($checkItems));
        $requestToken          = hash('sha256', $checkoutSession->getQuoteId() . ':' . Zend_Json::encode($createRequest));
        
        $this->_requestHashes = new Varien_Object(
            array(
            'order_item_request_token' => $orderItemRequestToken,
            'request_token'            => $requestToken
            )
        );

        return $this->_requestHashes;
    }

    /**
     * Compare a hash of the create order with a stored has to see if the payload has changed.
     *
     * @return string
     * @throws Klarna_Core_Exception
     */
    public function requestHashCheck()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');

        if (null === $this->_requestHashes) {
            throw new Klarna_Core_Exception('Hashes have not been generated for the current request.');
        }

        if ($checkoutSession->getKlarnaPaymentsItemCheckToken() !== $this->_requestHashes->getOrderItemRequestToken()) {
            return self::HASH_CHECK_ITEMS_CHANGED;
        }

        if ($checkoutSession->getKlarnaPaymentsPayloadToken() !== $this->_requestHashes->getRequestToken()) {
            return self::HASH_CHECK_CHANGED;
        }

        return self::HASH_CHECK_NO_CHANGE;
    }

    /**
     * Set session hashes from generated hashes
     *
     * @return $this
     * @throws Klarna_Core_Exception
     */
    public function setSessionRequestHashes()
    {
        if (null === $this->_requestHashes) {
            throw new Klarna_Core_Exception('Hashes have not been generated for the current request.');
        }

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->setKlarnaPaymentsPayloadToken($this->_requestHashes->getRequestToken());
        $checkoutSession->setKlarnaPaymentsItemCheckToken($this->_requestHashes->getOrderItemRequestToken());

        return $this;
    }

    /**
     * Check if Klarna Payments has already
     *
     * @return bool
     */
    public function isInitialized()
    {
        try {
            $this->createCheckHashes();
            if ($this->getKlarnaQuote()->getAuthorizationToken()
                && self::HASH_CHECK_ITEMS_CHANGED == $this->requestHashCheck()
            ) {
                $this->cancelKlarnaQuoteAuthorizationToken($this->getKlarnaQuote());
            }

            if (self::HASH_CHECK_NO_CHANGE !== $this->requestHashCheck()) {
                $this->setSessionRequestHashes();

                return false;
            }
        } catch (Klarna_Payments_BuilderException $e) {
            // Do not attempt to initialize with invalid create data
            Mage::logException($e);
            return false;
        }

        return (bool)($this->getKlarnaQuote()->getQuoteId());
    }

    /**
     * Initialize Klarna Payments session
     *
     * @param bool $forceInit
     *
     * @return $this
     */
    public function initKlarnaPayments($forceInit = false)
    {
        if ($forceInit || !$this->isInitialized()) {
            $klarnaCheckoutId      = $this->getKlarnaQuote()->getSessionId();
            $this->_klarnaPayments = $this->getPurchaseApiInstance()->initKlarnaSession($klarnaCheckoutId);

            $this->setKlarnaQuoteKlarnaTokens($this->_klarnaPayments);

            $klarnaCheckoutId = $this->getKlarnaQuote()->getSessionId();
            if (!is_null($klarnaCheckoutId)) {
                $this->_klarnaPayments = $this->getPurchaseApiInstance()->readSession($klarnaCheckoutId);
                $this->updatePaymentMethodCategories($this->_klarnaPayments->getPaymentMethodCategories());
            }
        } elseif (null === $this->_klarnaPayments) {
            $klarnaCheckoutId = $this->getKlarnaQuote()->getSessionId();
            if (!is_null($klarnaCheckoutId)) {
                $this->_klarnaPayments = $this->getPurchaseApiInstance()->readSession($klarnaCheckoutId);
                $this->updatePaymentMethodCategories($this->_klarnaPayments->getPaymentMethodCategories());
            } else {
                $this->_klarnaPayments = new Varien_Object();
            }
        }

        return $this;
    }

    /**
     * Recalculate totals before building request for Klarna
     *
     * @return $this
     */
    public function updateQuoteTotals()
    {
        if (!$this->_inQuoteUpdate && $this->getQuote() instanceof Mage_Sales_Model_Quote) {
            $this->_inQuoteUpdate = true;
            if (!$this->getQuote()->isVirtual()) {
                $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
            } else {
                $this->getQuote()->getBillingAddress()->setCollectShippingRates(true);
            }

            $this->getQuote()->collectTotals();
            $this->getQuote()->save();
            $this->_inQuoteUpdate = false;
        }

        return $this;
    }

    /**
     * Get current Klarna payments object
     *
     * @return Varien_Object
     */
    public function getKlarnaPayments()
    {
        if (null === $this->_klarnaPayments) {
            $this->initKlarnaPayments();
        }

        return $this->_klarnaPayments;
    }

    /**
     * Get Quote object based off current checkout quote
     *
     * @return Klarna_Payments_Model_Quote
     */
    public function getKlarnaQuote()
    {
        if (null === $this->_klarnaQuote) {
            $this->_klarnaQuote = Mage::getModel('klarna_payments/quote')->loadActiveByQuote($this->getQuote());
        }

        return $this->_klarnaQuote;
    }

    /**
     * Set Quote object
     *
     * @param Klarna_Payments_Model_Quote $klarnaQuote
     *
     * @return $this
     */
    public function setKlarnaQuote($klarnaQuote)
    {
        $this->_klarnaQuote = $klarnaQuote;

        return $this;
    }

    /**
     * Set the Klarna session id
     *
     * @param Varien_Object $klarnaPayments
     *
     * @return $this
     */
    public function setKlarnaQuoteKlarnaTokens(Varien_Object $klarnaPayments)
    {
        if (!$klarnaPayments->getClientToken() || !$klarnaPayments->getSessionId()) {
            return $this;
        }

        $klarnaQuote = $this->getKlarnaQuote();

        if ($klarnaQuote->getId()) {
            if ($klarnaQuote->getSessionId() != $klarnaPayments->getSessionId()) {
                $klarnaQuote->setIsActive(0);
                $klarnaQuote->save();

                if ($klarnaQuote->getAuthorizationToken()) {
                    $this->cancelKlarnaQuoteAuthorizationToken($klarnaQuote);
                }

                $klarnaQuote = $this->_createNewKlarnaQuote($klarnaPayments);
            }
        } else {
            $klarnaQuote = $this->_createNewKlarnaQuote($klarnaPayments);
        }

        $this->setKlarnaQuote($klarnaQuote);

        return $this;
    }

    /**
     * @param array|null $paymentMethodCategories
     * @return $this
     * @throws Exception
     */
    private function updatePaymentMethodCategories($paymentMethodCategories)
    {
        $klarnaQuote = $this->getKlarnaQuote();
        $klarnaQuote->setPaymentMethodCategories($paymentMethodCategories);
        $klarnaQuote->save();

        return $this;
    }

    /**
     * Create a new klarna quote object
     *
     * @param Varien_Object $klarnaPayments
     *
     * @return Klarna_Payments_Model_Quote
     * @throws Exception
     */
    protected function _createNewKlarnaQuote($klarnaPayments)
    {
        $klarnaQuote = Mage::getModel('klarna_payments/quote');
        $klarnaQuote->setData(
            array(
            'session_id' => $klarnaPayments->getSessionId(),
            'is_active'  => 1,
            'quote_id'   => $this->getQuote()->getId(),
            )
        );

        if ($klarnaPayments->getClientToken()) {
            $klarnaQuote->setClientToken($klarnaPayments->getClientToken());
            $klarnaQuote->setPaymentMethodCategories($klarnaPayments->getPaymentMethodCategories());
        }

        $klarnaQuote->save();

        return $klarnaQuote;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::helper('checkout')->getQuote();
    }

    /**
     * Get Klarna payments api instance
     *
     * @return Klarna_Payments_Model_Api_Kasper_Purchase
     */
    public function getPurchaseApiInstance()
    {
        return Mage::helper('klarna_core')->getPurchaseApiInstance('klarna_payments');
    }

    /**
     * Check if is onestepcheckout
     *
     * @return bool
     */
    public function isOneStepCheckout()
    {
        return Mage::app()->getRequest()->getModuleName() === 'onestepcheckout';
    }
}
