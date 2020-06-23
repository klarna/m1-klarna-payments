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
 * Klarna payments payment method
 */
class Klarna_Payments_Model_Payment_Payments extends Klarna_Core_Model_Payment_Method_Abstract
{
    protected $_code          = 'klarna_payments';
    protected $_formBlockType = 'klarna_payments/form_payments';
    protected $_infoBlockType = 'klarna_payments/info_payments';

    /**
     * Availability options
     */
    protected $_isGateway                 = false;
    protected $_canOrder                  = false;
    protected $_canAuthorize              = true;
    protected $_canCapture                = true;
    protected $_canCapturePartial         = true;
    protected $_canRefund                 = true;
    protected $_canRefundInvoicePartial   = true;
    protected $_canVoid                   = true;
    protected $_canUseInternal            = false;
    protected $_canUseCheckout            = true;
    protected $_canUseForMultishipping    = false;
    protected $_canFetchTransactionInfo   = true;
    protected $_canCreateBillingAgreement = false;
    protected $_canReviewPayment          = false;
    protected $_isInitializeNeeded        = false;

    /**
     * Available payment method categories which are returned rom the API and includes text and
     * logo of the respective payments
     *
     * @var array
     */
    protected $_categoryInformation = array();

    /**
     * The title of the payment for example 'Pay now'
     *
     * @var string
     */
    protected $_title = '';

    /**
     * @var Klarna_Payments_Model_Quote
     */
    protected $_klarnaQuote = null;

    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    public function setCategoryInformation(array $information)
    {
        $this->_categoryInformation = $information;
        return $this;
    }

    public function getCategoryInformation()
    {
        return $this->_categoryInformation;
    }

    /**
     * Check if the Klarna Payments is available
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (null === $quote) {
            return parent::isAvailable($quote);
        }

        if (!parent::isAvailable($quote)) {
            return false;
        }

        if (!$quote->getIsActive()) {
            return true;
        }

        $isAvailable = false;

        try {
            Mage::helper('klarna_payments/checkout')->initKlarnaPayments();
            $isAvailable = true;

            $klarnaQuote = $this->_getKlarnaQuote($quote);
            $paymentMethodCategories = $klarnaQuote->getPaymentMethodCategories();

            $checkoutSession = Mage::getSingleton('checkout/session');
            $checkoutSession->setData($this->getCode() . '_categories_block_form', $paymentMethodCategories);
            $checkoutSession->setData($this->getCode() . '_categories_block_payment', $paymentMethodCategories);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return !$isAvailable ? false : parent::isAvailable($quote);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Assign method variables
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        if ($data->getAuthorizationToken()) {
            $this->_getKlarnaQuote($info->getQuote())
                ->setAuthorizationToken($data->getAuthorizationToken())
                ->save();
        }

        return $this;
    }

    /**
     * Validate the authorization payment method
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        $info  = $this->getInfoInstance();
        $quote = null;

        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            $quote = $info->getQuote();
        }

        if ($info instanceof Mage_Sales_Model_Order_Payment) {
            $quote = $info->getOrder()->getQuote();
        }

        if (null !== $quote) {
            $klarnaQuote = $this->_getKlarnaQuote($quote);

            if ($info->getAuthorizationToken()) {
                $klarnaQuote = $klarnaQuote->setAuthorizationToken($info->getAuthorizationToken())
                    ->save();
            }

            if (!$klarnaQuote->getAuthorizationToken()) {
                Mage::throwException($this->_getHelper()->__("Authorization Token is a required field."));
            }
        }

        return $this;
    }

    /**
     * Authorize payment method
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $klarnaQuote = $this->_getKlarnaQuote($payment->getOrder()->getQuote());
        $result      = $this->getPurchaseApi()->placeOrder(
            $klarnaQuote->getAuthorizationToken(), $this->getPurchaseApi()->getGeneratedPlaceRequest()
        );

        if ($result->getIsSuccessful()) {
            switch ($result->getFraudStatus()) {
                case self::FRAUD_STATUS_REJECTED:
                    $payment->setIsFraudDetected(true);
                    break;
                case self::FRAUD_STATUS_PENDING:
                    $payment->setIsTransactionPending(true);
                    break;
            }

            Mage::unregister('klarna_payments_redirect_url');
            Mage::register('klarna_payments_redirect_url', $result->getRedirectUrl());

            $klarnaOrder = Mage::getModel('klarna_core/order');

            $klarnaOrder->setData(
                array(
                'session_id'     => $result->getId(),
                'reservation_id' => $result->getId(),
                'order_id'       => $payment->getOrder()->getId()
                )
            );
            $klarnaOrder->save();

            if (!$klarnaOrder->getId() || !$klarnaOrder->getReservationId()) {
                Mage::throwException('Unable to authorize payment for this order.');
            }

            $payment->setTransactionId($result->getId())->setIsTransactionClosed(0);
        } else {
            Mage::helper('klarna_payments/checkout')->cancelKlarnaQuoteAuthorizationToken($klarnaQuote);
            Mage::throwException('Unable to authorize payment for this order.');
        }

        return $this;
    }

    /**
     * Get redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::registry('klarna_payments_redirect_url');
    }

    /**
     * Get Klarna quote for a sales quote
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Klarna_Payments_Model_Quote
     */
    protected function _getKlarnaQuote($quote)
    {
        if (null === $this->_klarnaQuote) {
            $this->_klarnaQuote = Mage::getModel('klarna_payments/quote')->loadActiveByQuote($quote);
        }

        return $this->_klarnaQuote;
    }
}
