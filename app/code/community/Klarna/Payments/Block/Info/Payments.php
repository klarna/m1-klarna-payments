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
 * Klarna payments payment info
 */
class Klarna_Payments_Block_Info_Payments extends Mage_Payment_Block_Info
{
    /**
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('klarnapayments/payment/info.phtml');
    }

    /**
     * Prepare information for payment
     *
     * @param Varien_Object|array $transport
     *
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport         = parent::_prepareSpecificInformation($transport);
        $info              = $this->getInfo();
        $klarnaReferenceId = $info->getAdditionalInformation('klarna_reference');
        $order             = $info->getOrder();
        if ($order) {
            $klarnaOrder  = Mage::getModel('klarna_core/order')->loadByOrder($order);
            $isAdminBlock = $this->getParentBlock()
                && $this->getParentBlock() instanceof Mage_Adminhtml_Block_Sales_Order_Payment;

            if ($isAdminBlock && $klarnaOrder->getId() && $klarnaOrder->getSessionId()) {
                $transport->setData($this->helper('klarna_payments')->__('Order ID'), $klarnaOrder->getSessionId());

                $transport->setData(
                    $this->helper('klarna_payments')->__('Payment Method'),
                    $this->helper('klarna_payments')->getKlarnaPaymentNameByType(
                        $info->getAdditionalInformation('klarna_payment_type'),
                        $order->getQuoteId()
                    )
                );

                $transport->setData(
                    $this->helper('klarna_payments')->__('Merchant Portal'),
                    $this->helper('klarna_payments')->getOrderMerchantPortalLink($order,$klarnaOrder)
                );

                if ($klarnaOrder->getReservationId()
                    && $klarnaOrder->getReservationId() != $klarnaOrder->getSessionId()
                ) {
                    $transport->setData(
                        $this->helper('klarna_core')
                        ->__('Reservation'), $klarnaOrder->getReservationId()
                    );
                }
            }

            if ($klarnaReferenceId) {
                $transport->setData($this->helper('klarna_payments')->__('Reference'), $klarnaReferenceId);
            }

            $invoices = $order->getInvoiceCollection();
            foreach ($invoices as $invoice) {
                if ($invoice->getTransactionId()) {
                    $invoiceKey = $this->helper('klarna_payments')->__('Invoice ID (#%s)', $invoice->getIncrementId());
                    $transport->setData($invoiceKey, $invoice->getTransactionId());
                }
            }
        }

        return $transport;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $payment = $checkoutSession->getData($this->getMethod()->getCode() . '_selected');

        if (is_null($payment)) {
            return '';
        }

        /** @var Klarna_Payments_Model_Quote $klarnaQuote */
        $klarnaQuote = Mage::helper('klarna_payments/checkout')->getKlarnaQuote();
        $paymentMethodCategories = $klarnaQuote->getPaymentMethodCategories();

        foreach ($paymentMethodCategories as $methodCategory) {
            if ($methodCategory['identifier'] == $payment) {
                return $methodCategory['asset_urls']['descriptive'];
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getName()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $name = $checkoutSession->getData($this->getMethod()->getCode() . '_selected');

        if (is_null($name)) {
            return $this->escapeHtml($this->getMethod()->getTitle());
        }

        /** @var Klarna_Payments_Model_Quote $klarnaQuote */
        $klarnaQuote = Mage::helper('klarna_payments/checkout')->getKlarnaQuote();
        $paymentMethodCategories = $klarnaQuote->getPaymentMethodCategories();

        foreach ($paymentMethodCategories as $methodCategory) {
            if ($methodCategory['identifier'] == $name) {
                return $this->escapeHtml($methodCategory['name']);
            }
        }

        return '';
    }

    /**
     * Check if string is a url
     *
     * @param $string
     * @return bool
     */
    public function isStringUrl($string)
    {
        return (bool)filter_var($string, FILTER_VALIDATE_URL);
    }
}
