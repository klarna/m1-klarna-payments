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
 * Abstract class to generate checkout configuration
 *
 * @method Klarna_Core_Model_Api_Builder_Abstract setShippingUnitPrice($integer)
 * @method int getShippingUnitPrice()
 * @method Klarna_Core_Model_Api_Builder_Abstract setShippingTaxRate($integer)
 * @method int getShippingTaxRate()
 * @method Klarna_Core_Model_Api_Builder_Abstract setShippingTotalAmount($integer)
 * @method int getShippingTotalAmount()
 * @method Klarna_Core_Model_Api_Builder_Abstract setShippingTaxAmount($integer)
 * @method int getShippingTaxAmount()
 * @method Klarna_Core_Model_Api_Builder_Abstract setShippingTitle($integer)
 * @method int getShippingTitle()
 * @method Klarna_Core_Model_Api_Builder_Abstract setShippingReference($integer)
 * @method int getShippingReference()
 * @method Klarna_Core_Model_Api_Builder_Abstract setDiscountUnitPrice($integer)
 * @method int getDiscountUnitPrice()
 * @method Klarna_Core_Model_Api_Builder_Abstract setDiscountTaxRate($integer)
 * @method int getDiscountTaxRate()
 * @method Klarna_Core_Model_Api_Builder_Abstract setDiscountTotalAmount($integer)
 * @method int getDiscountTotalAmount()
 * @method Klarna_Core_Model_Api_Builder_Abstract setDiscountTaxAmount($integer)
 * @method int getDiscountTaxAmount()
 * @method Klarna_Core_Model_Api_Builder_Abstract setDiscountTitle($integer)
 * @method int getDiscountTitle()
 * @method Klarna_Core_Model_Api_Builder_Abstract setDiscountReference($integer)
 * @method int getDiscountReference()
 * @method Klarna_Core_Model_Api_Builder_Abstract setTaxUnitPrice($integer)
 * @method int getTaxUnitPrice()
 * @method Klarna_Core_Model_Api_Builder_Abstract setTaxTotalAmount($integer)
 * @method int getTaxTotalAmount()
 * @method Klarna_Core_Model_Api_Builder_Abstract setItems(array $array)
 * @method array getItems()
 */
class Klarna_Core_Model_Api_Builder_Abstract extends Varien_Object
{
    /**
     * @var Klarna_Core_Model_Api_Builder_Orderline_Collector
     */
    protected $_orderLineCollector = null;

    /**
     * @var array
     */
    protected $_orderLines = array();

    /**
     * @var Mage_Sales_Model_Abstract|Mage_Sales_Model_Quote
     */
    protected $_object = null;

    /**
     * @var Klarna_Core_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var array
     */
    protected $_request = array();

    /**
     * @var bool
     */
    protected $_inRequestSet = false;

    /**
     * Generate types
     */
    const GENERATE_TYPE_CREATE = 'create';
    const GENERATE_TYPE_UPDATE = 'update';

    /**
     * Init
     */
    public function _construct()
    {
        $this->_helper = Mage::helper('klarna_core');
    }

    /**
     * Generate order payload
     *
     * @param string $type
     *
     * @return $this
     */
    public function generateRequest($type = self::GENERATE_TYPE_CREATE)
    {
        $this->collectOrderLines();

        $data = $this->_generateRequest($type);

        $this->setRequest($data, $type);

        return $this;
    }

    /**
     * Generate the request
     *
     * @param string $type
     *
     * @return array
     */
    protected function _generateRequest($type)
    {
        return array();
    }

    /**
     * Get request
     *
     * @return array
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set generated request
     *
     * @param array  $request
     * @param string $type
     *
     * @return $this
     */
    public function setRequest(array $request, $type = self::GENERATE_TYPE_CREATE)
    {
        $this->_request = $request;

        if (!$this->_inRequestSet) {
            $this->_inRequestSet = true;
            Mage::dispatchEvent(
                "klarna_request_builder_set_request_{$type}", array(
                'builder' => $this
                )
            );

            Mage::dispatchEvent(
                'klarna_request_builder_set_request', array(
                'builder' => $this
                )
            );
            $this->_inRequestSet = false;
        }

        return $this;
    }

    /**
     * Get the object used to generate request
     *
     * @return Mage_Sales_Model_Abstract|Mage_Sales_Model_Quote
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Set the object used to generate request
     *
     * @param Mage_Sales_Model_Abstract|Mage_Sales_Model_Quote $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->_orderLineCollector = null;
        $this->_object             = $object;

        return $this;
    }

    /**
     * Get totals collector model
     *
     * @return Klarna_Core_Model_Api_Builder_Orderline_Collector
     */
    public function getOrderLinesCollector()
    {
        if (null === $this->_orderLineCollector) {
            $this->_orderLineCollector = Mage::getSingleton(
                'klarna_core/api_builder_orderline_collector',
                array('store' => $this->getObject()->getStore())
            );
        }

        return $this->_orderLineCollector;
    }

    /**
     * Collect order lines
     *
     * @return $this
     */
    public function collectOrderLines()
    {
        /** @var Klarna_Core_Model_Api_Builder_Orderline_Abstract $model */
        foreach ($this->getOrderLinesCollector()->getCollectors() as $model) {
            $model->collect($this);
        }

        return $this;
    }

    /**
     * Get order lines as array
     *
     * @param bool $orderItemsOnly
     *
     * @return array
     */
    public function getOrderLines($orderItemsOnly = false)
    {
        /** @var Klarna_Core_Model_Api_Builder_Orderline_Abstract $model */
        foreach ($this->getOrderLinesCollector()->getCollectors() as $model) {
            if ($model->isIsTotalCollector() && $orderItemsOnly) {
                continue;
            }

            $model->fetch($this);
        }

        return $this->_orderLines;
    }

    /**
     * Add an order line
     *
     * @param array $orderLine
     *
     * @return $this
     */
    public function addOrderLine(array $orderLine)
    {
        $this->_orderLines[] = $orderLine;

        return $this;
    }

    /**
     * Remove all order lines
     *
     * @return $this
     */
    public function resetOrderLines()
    {
        $this->_orderLines = array();

        return $this;
    }

    /**
     * Auto fill user address details
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string                 $type
     *
     * @return array
     */
    protected function _getAddressData($quote, $type = null)
    {
        $result = array();
        if ($quote->getCustomerEmail()) {
            $result['email'] = $quote->getCustomerEmail();
        }

        $customer = $quote->getCustomer();

        if ($quote->isVirtual() || $type == Mage_Sales_Model_Quote_Address::TYPE_BILLING) {
            $address = $quote->getBillingAddress();
            if ($customer->getId() && !$this->_isAddressValid($address)) {
                $address = $customer->getDefaultBillingAddress();
            }
        } else {
            $address = $quote->getShippingAddress();

            if ($customer->getId() && !$this->_isAddressValid($address)) {
                $address = $customer->getDefaultShippingAddress();
            }
        }

        $resultObject = new Varien_Object($result);
        if ($address) {
            $address->explodeStreetAddress();
            Mage::helper('core')->copyFieldset('convert_quote_address', 'to_klarna', $address, $resultObject, 'klarna');
        }
        return array_filter($resultObject->toArray());
    }

    /**
     * Get available shipping methods for a quote for the api init
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function _getShippingMethods($quote)
    {
        $rates = array();
        if ($quote->isVirtual()) {
            return $rates;
        }

        /** @var Mage_Sales_Model_Quote_Address_Rate $rate */
        foreach ($quote->getShippingAddress()->getAllShippingRates() as $rate) {
            if (!$rate->getCode() || !$rate->getMethodTitle()) {
                continue;
            }

            $rates[] = array(
                'id'          => $rate->getCode(),
                'name'        => $rate->getMethodTitle(),
                'price'       => $this->_helper->toApiFloat($rate->getPrice()),
                'promo'       => '',
                'tax_amount'  => 0,
                'tax_rate'    => 0,
                'description' => $rate->getMethodDescription(),
                'preselected' => $rate->getCode() == $quote->getShippingAddress()->getShippingMethod()
            );
        }

        return $rates;
    }

    /**
     * Get customer details
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function _getCustomerData($quote)
    {
        if (!$quote->getCustomerIsGuest() && $quote->getCustomerDob()) {
            return array(
                'date_of_birth' => Varien_Date::formatDate(strtotime($quote->getCustomerDob()), false)
            );
        }

        return array();
    }

    /**
     * Get default store country
     *
     * @param null $store
     *
     * @return mixed|string
     */
    public function getDefaultCountry($store = null)
    {
        if (version_compare(Mage::getVersion(), '1.6.2', '>=')) {
            return Mage::helper('core')->getDefaultCountry($store);
        }

        return Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_COUNTRY, $store);
    }

    /**
     * check if address is valid
     *
     * @param $address
     *
     * @return bool
     */
    protected function _isAddressValid($address)
    {
        if (!$address->getPostcode()) {
            return false;
        }
        // If this is OSC checkout
        if (Mage::app()->getRequest()->getRouteName() === 'onestepcheckout') {
            if (!$address->getStreet()) {
                return false;
            }

            if (!$address->getFirstname()) {
                return false;
            }

            if (!$address->getLastname()) {
                return false;
            }
        }
        return true;
    }
}
