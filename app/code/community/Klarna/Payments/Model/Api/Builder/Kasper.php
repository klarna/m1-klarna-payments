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
 * Klarna payments kasper order payload builder
 */
class Klarna_Payments_Model_Api_Builder_Kasper extends Klarna_Core_Model_Api_Builder_Abstract
{
    /**
     * @var Klarna_Payments_Helper_Checkout
     */
    protected $_checkoutHelper = null;

    /**
     * @var Klarna_Payments_Helper_Data
     */
    protected $_paymentsHelper = null;

    /**
     * @var null
     */
    protected $_attachmentDataCollector = null;

    /**
     * @var array
     */
    protected $_attachmentData = array();

    /**
     * Generate types
     */
    const GENERATE_TYPE_PLACE         = 'place';
    const GENERATE_TYPE_CLIENT_UPDATE = 'client_update';

    /**
     * Init
     */
    public function _construct()
    {
        Klarna_Core_Model_Api_Builder_Abstract::_construct();
        $this->_paymentsHelper = Mage::helper('klarna_payments');
        $this->_checkoutHelper = Mage::helper('klarna_payments/checkout');
    }

    /**
     * Generate request
     *
     * @param string $type
     *
     * @return array
     */
    public function _generateRequest($type = self::GENERATE_TYPE_CREATE)
    {
        switch ($type) {
            case self::GENERATE_TYPE_CREATE:
            case self::GENERATE_TYPE_UPDATE:
                return $this->_generateCreateUpdate();
            case self::GENERATE_TYPE_PLACE:
                return $this->_generatePlace();
            case self::GENERATE_TYPE_CLIENT_UPDATE:
                return $this->_generateClientUpdate();
        }

        return array();
    }

    /**
     * Generate body for create and update
     *
     * @return array
     * @throws Klarna_Payments_BuilderException
     */
    protected function _generateCreateUpdate()
    {
        $requiredAttributes = array(
            'purchase_country', 'purchase_currency', 'locale', 'order_amount', 'order_lines'
        );

        /** @var Mage_Sales_Model_Quote $quote */
        $quote  = $this->getObject();
        $store  = $quote->getStore();
        $create = array();

        /** @var Mage_Sales_Model_Quote_Address $billingAddress */
        $billingAddress = $quote->getBillingAddress();
        $country = $this->getDefaultCountry($store);
        if (!is_null($billingAddress)) {
            $billingCountry = $billingAddress->getCountry();
            if (!empty($billingCountry)) {
                $country = $billingCountry;
            }
        }

        $currency = $quote->getBaseCurrencyCode();
        $quoteCurrency = $quote->getQuoteCurrencyCode();
        if (!empty($quoteCurrency)) {
            $currency = $quoteCurrency;
        }

        $create['purchase_country']  = $country;
        $create['purchase_currency'] = $currency;
        $create['locale']            = str_replace('_', '-', Mage::app()->getLocale()->getLocaleCode());
        $create['order_lines']       = $this->getOrderLines();

        // @todo customer payment methods

        if ($this->_paymentsHelper->getDataSharingEnabled($store)
            && Mage::getStoreConfig('klarna/api/api_version', $store) === 'na') {
            $create['billing_address']  = $this->_getAddressData($quote, Mage_Sales_Model_Quote_Address::TYPE_BILLING);
            $create['shipping_address'] = $this->_getAddressData($quote, Mage_Sales_Model_Quote_Address::TYPE_SHIPPING);
            $create['customer']         = $this->_getCustomerData($quote);
        }

        /**
         * Urls
         */
        $urlParams = array(
            '_nosid'         => true,
            '_forced_secure' => true
        );

        $create['merchant_urls'] = array(
            'confirmation' => Mage::getUrl('checkout/onepage/success', $urlParams),
            'notification' => Mage::getUrl('klarna/notification', $urlParams)
        );

        /**
         * Merchant reference
         */
        $merchantReferences = new Varien_Object(
            array(
            'merchant_reference1' => $quote->getReservedOrderId()
            )
        );

        Mage::dispatchEvent(
            'klarna_payments_merchant_reference_update', array(
            'quote'                     => $quote,
            'merchant_reference_object' => $merchantReferences
            )
        );

        if ($merchantReferences->getData('merchant_reference1')) {
            $create['merchant_reference1'] = $merchantReferences->getData('merchant_reference1');
        }

        if (!empty($merchantReferences['merchant_reference2'])) {
            $create['merchant_reference2'] = $merchantReferences->getData('merchant_reference2');
        }

        /**
         * Options
         */
        $create['options'] = array_map('trim', array_filter($this->_checkoutHelper->getCheckoutDesignConfig($store)));

        // @todo attachments
        
        $create = array_filter($create);

        $address = $quote->isVirtual() ? $quote->getBillingAddress()
            : $quote->getShippingAddress();
        $create['order_amount']      = $this->_helper->toApiFloat($address->getBaseGrandTotal());
        $create['order_tax_amount']  = $this->_helper->toApiFloat($address->getBaseTaxAmount());

        $missingAttributes = array();
        foreach ($requiredAttributes as $requiredAttribute) {
            if (!isset($create[$requiredAttribute])) { // don't use empty since 0 is equivelant to false
                $missingAttributes[] = $requiredAttribute;
            }
        }

        if (isset($create['billing_address']) && !isset($create['billing_address']['email'])) {
            $missingAttributes[] = 'email';
        }

        if (isset($create['shipping_address']) && !isset($create['shipping_address']['email'])) {
            $missingAttributes[] = 'email';
        }

        if (!empty($missingAttributes)) {
            throw new Klarna_Payments_BuilderException(sprintf('Missing required attribute(s) on create/update: "%s".', implode(', ', $missingAttributes)));
        }

        $total = 0;
        foreach ($create['order_lines'] as $orderLine) {
            $total += $orderLine['total_amount'];
        }

        $requestDataObj = new Varien_Object();
        $requestDataObj->setRequestBody($create);
        Mage::dispatchEvent(
            'klarna_payments_request_create_after', array(
                'request_object' => $requestDataObj
            )
        );

        $create = $requestDataObj->getRequestBody();

        if ($total != $create['order_amount']) {
            throw new Klarna_Payments_BuilderException(sprintf('Order line totals do not total order_amount "%s" != "%s"', $total, $create['order_amount']));
        }

        foreach ($create['order_lines'] as $orderLine) {
            if ($orderLine['total_amount'] != $orderLine['quantity'] * $orderLine['unit_price']) {
                throw new Klarna_Payments_BuilderException('Order line totals do not total unit_price x qty');
            }
        }

        $attachmentData =  $this->getAttachmentData();
        if ($attachmentData) {
            $create['attachment'] = $attachmentData;
        }

        return $create;
    }

    /**
     * Generate place order body
     *
     * @return array
     */
    protected function _generatePlace()
    {
        $requiredAttributes = array(
            'purchase_country', 'purchase_currency', 'locale', 'order_amount', 'order_lines', 'merchant_urls',
            'billing_address', 'shipping_address'
        );

        /** @var Mage_Sales_Model_Quote $quote */
        $quote  = $this->getObject();
        $create = array();

        $create['locale']            = str_replace('_', '-', Mage::app()->getLocale()->getLocaleCode());
        $create['purchase_country']  = $this->getDefaultCountry();
        $create['purchase_currency'] = $quote->getBaseCurrencyCode();
        $create['billing_address']   = $this->_getAddressData($quote, Mage_Sales_Model_Quote_Address::TYPE_BILLING);
        $create['shipping_address']  = $this->_getAddressData($quote, Mage_Sales_Model_Quote_Address::TYPE_SHIPPING);

        $address                    = $quote->isVirtual() ? $quote->getBillingAddress()
            : $quote->getShippingAddress();
        $create['order_amount']     = $this->_helper->toApiFloat($address->getBaseGrandTotal());
        $create['order_tax_amount'] = $this->_helper->toApiFloat($address->getBaseTaxAmount());
        $create['order_lines']      = $this->getOrderLines();

        $attachmentData = $this->getAttachmentData();
        if ($attachmentData) {
            $create['attachment'] = $attachmentData;
        }

        /**
         * Urls
         */
        $urlParams = array(
            '_nosid'         => true,
            '_forced_secure' => true
        );

        $create['merchant_urls'] = array(
            'confirmation' => Mage::getUrl('checkout/onepage/success', $urlParams),
            'notification' => Mage::getUrl('klarna/notification', $urlParams)
        );

        /**
         * Merchant reference
         */
        $merchantReferences = new Varien_Object(
            array(
            'merchant_reference1' => $quote->getReservedOrderId()
            )
        );

        Mage::dispatchEvent(
            'klarna_payments_merchant_reference_update', array(
            'quote'                     => $quote,
            'merchant_reference_object' => $merchantReferences
            )
        );

        if ($merchantReferences->getData('merchant_reference1')) {
            $create['merchant_reference1'] = $merchantReferences->getData('merchant_reference1');
        }

        if (!empty($merchantReferences['merchant_reference2'])) {
            $create['merchant_reference2'] = $merchantReferences->getData('merchant_reference2');
        }

        $create = array_filter($create);

        $missingAttributes = array();
        foreach ($requiredAttributes as $requiredAttribute) {
            if (empty($create[$requiredAttribute])) {
                $missingAttributes[] = $requiredAttribute;
            }
        }

        if (!empty($missingAttributes)) {
            Mage::throwException(sprintf('Missing required attribute(s) on place: "%s".', implode(', ', $missingAttributes)));
        }

        $total = 0;
        foreach ($create['order_lines'] as $orderLine) {
            $total += $orderLine['total_amount'];
        }

        if ($total != $create['order_amount']) {
            Mage::throwException('Order line totals do not total order_amount');
        }

        return $create;
    }

    /**
     * Generate request for client side update
     *
     * @return array
     */
    protected function _generateClientUpdate()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getObject();

        return array_filter(array(
            'customer' => $this->getCustomerUpdateData($quote),
            'billing_address' => $this->_getAddressData($quote, Mage_Sales_Model_Quote_Address::TYPE_BILLING),
            'shipping_address' => $this->_getAddressData($quote, Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
        ));
    }

    /**
     * Get customer details
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    private function getCustomerUpdateData($quote)
    {
        $customerData = array();
        if ($quote->getCustomerDob()) {
            $customerData['date_of_birth'] = Varien_Date::formatDate(strtotime($quote->getCustomerDob()), false);
        }

        $dobFromOsc = $this->extractDOBFromRequest();
        if ($dobFromOsc !== false) {
            $customerData['date_of_birth'] = $dobFromOsc;
        }

        $customerUpdateData = new Varien_Object(
            array(
                'customer_info' => $customerData
            )
        );
        //add additional customer data though observing this event
        Mage::dispatchEvent(
            'klarna_payments_get_customer_update_data', array(
                'quote' => $quote,
                'customer_data' => $customerUpdateData
            )
        );
        $customerData = $customerUpdateData->getData('customer_info');
        return count($customerData) > 0 ? $customerData : false;
    }

    /**
     * Get dob from post data for osc checkout
     *
     * @return bool|null|string
     */
    private function extractDOBFromRequest()
    {
        $requestData = Mage::app()->getRequest()->getParam('billing');
        if ($requestData) {
            if (!empty($requestData['day']) && !empty($requestData['month']) && !empty($requestData['year'])) {
                $date = $requestData['year'] . "-" . $requestData['month'] . "-" . $requestData['day'];
                return Varien_Date::formatDate(strtotime($date), false);
            }
        }
        return false;
    }

    /**
     * @return array|bool
     */
    public function getAttachmentData()
    {
        /** @var Klarna_Kco_Model_Checkout_Attachment_Abstract $model */
        foreach ($this->getAttachmentDataCollector()->getCollectors() as $model) {
            $model->fetch($this);
        }
        if (empty($this->_attachmentData)) {
            return false;
        } else {
            return array(
                'content_type' => 'application/vnd.klarna.internal.emd-v2+json',
                'body' => json_encode($this->_attachmentData)
            );

        }
    }

    /**
     * Get attachment collector model
     *
     * @return Mage_Core_Model_Abstract|null
     */
    public function getAttachmentDataCollector()
    {
        if (null === $this->_attachmentDataCollector) {
            $this->_attachmentDataCollector = Mage::getSingleton(
                'klarna_payments/payment_attachment_collector',
                array('store' => $this->getObject()->getStore())
            );
        }
        return $this->_attachmentDataCollector;
    }

    /**
     * Collect attachment
     *
     * @return $this
     */
    public function collectAttachmentData()
    {
        foreach ($this->getAttachmentDataCollector()->getCollectors() as $model) {
            $model->collect($this);
        }
        return $this;
    }

    /**
     * Add attachment data
     *
     * @param array $attachmentData
     *
     * @return $this
     */
    public function addAttachmentData(array $attachmentData)
    {
        foreach ($attachmentData as $key => $var) {
            $this->_attachmentData[$key][] = $var;

        }
        return $this;
    }

    /**
     * remove attachment data
     *
     * @return $this
     */
    public function resetAttachmentData()
    {
        $this->_attachmentData = array();
        return $this;
    }
}
