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
 * Klarna payments payment form block
 */
class Klarna_Payments_Block_Form_Payments extends Mage_Payment_Block_Form
{
    /**
     * get klarna container id
     *
     * @return string
     */
    public function getContainerId()
    {
        return 'klarna-payments-container';
    }

    /**
     * get credit function name
     *
     * @return string
     */
    public function getFunctionName()
    {
        return 'Payments';
    }

    /**
     * Check if an authorization token has been set
     *
     * @return bool
     */
    public function hasAuthorizationToken()
    {
        return (bool)$this->getAuthorizationToken();
    }

    /**
     * Get the authorization token
     *
     * @return string
     */
    public function getAuthorizationToken()
    {
        return $this->getKlarnaQuote()->getAuthorizationToken();
    }

    /**
     * Get Klarna quote details
     *
     * @return Klarna_Payments_Model_Quote|Varien_Object
     */
    public function getKlarnaQuote()
    {
        try {
            return Mage::helper('klarna_payments/checkout')->getKlarnaQuote();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return new Varien_Object();
    }

    /**
     * Get client token for checkout session
     *
     * @return string
     */
    public function getClientToken()
    {
        return $this->getKlarnaQuote()->getClientToken();
    }

    /**
     * @return array
     */
    public function getMethodCategoryInformation()
    {
        return $this->getMethod()->getCategoryInformation();
    }

    /**
     * If the Klarna pre-screen is enabled
     *
     * @return bool
     */
    public function getPreScreenEnabled()
    {
        return (bool)$this->getMethod()->getConfigData('pre_screen');
    }

    /**
     * Get order update json
     *
     * @return string
     */
    public function getOrderUpdateDataJson()
    {
        $data = Mage::helper('klarna_payments/checkout')->getPurchaseApiInstance()->getGeneratedClientUpdateRequest();

        return Mage::helper('core')->jsonEncode($data);
    }

    /**
     * Set the payment method and the template mark
     *
     * @param Klarna_Payments_Block_Form_Payments $method
     * @return Varien_Object
     */
    public function setMethod($method)
    {
        $result = $this->setData('method', $method);

        $payment = $this->getCorrectPayment($method);
        if (empty($payment)) {
            return $result;
        }

        $mark = Mage::getConfig()->getBlockClassName('klarna_payments/mark');

        /** @var Klarna_Payments_Block_Mark $mark */
        $mark = new $mark;
        $mark->setMethodCode($this->getKlarnaMethodCode());
        $mark->setTitle($payment['name']);
        $mark->setLogo($payment['asset_urls']['descriptive']);

        $this->setTemplate('klarnapayments/payment/form.phtml')
            ->setMethodTitle('')// Output Klarna mark, omit title
            ->setMethodLabelAfterHtml($mark->toHtml())
            ->setCacheLifetime(null);

        $result = $this->setData('method', $method);
        return $result;
    }

    /**
     * @param Klarna_Payments_Block_Form_Payments $method
     * @return array
     */
    private function getCorrectPayment($method)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $payments = $checkoutSession->getData($this->getKlarnaMethodCode() . '_categories_block_form');

        $keySearch = str_replace('klarna_payments_', '', $method->getCode());
        $payment = array();
        foreach ($payments as $key => $value) {
            if ($value['identifier'] == $keySearch) {
                $payment = $value;
                unset($payments[$key]);
                break;
            }
        }

        $checkoutSession->setData($this->getKlarnaMethodCode() . '_categories_block_form', $payments);

        return $payment;
    }

    /**
     * get klarna method code
     *
     * @return string
     */
    protected function getKlarnaMethodCode()
    {
        return 'klarna_payments';
    }

    /**
     * get is one step checkout
     *
     * @return bool
     */
    public function isOneStepCheckout()
    {
        return Mage::helper('klarna_payments/checkout')->isOneStepCheckout();
    }

    /**
     * Get is Auto finalize by determine if default checkout is used
     *
     * @return bool
     */
    public function isAutoFinalize()
    {
        $controllerName = Mage::app()->getRequest()->getControllerName();
        return (bool)($controllerName !== 'onepage');
    }
}
