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
 * Klarna Payments observer methods
 */
class Klarna_Payments_Model_Observer
{

    /**
     * Changing the klarna payment code to its default
     *
     * @param Varien_Event_Observer $observer
     */
    public function adjustPaymentMethodCode(Varien_Event_Observer $observer)
    {
        /** @var Varien_Object $input */
        $input = $observer->getData()['input'];
        $method = $input->getData('method');

        $klarnaKeyStart = 'klarna_payments_';
        if (strpos($method, $klarnaKeyStart) !== 0) {
            return;
        }

        $paymentKey = substr($method, strlen($klarnaKeyStart));

        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->setData($klarnaKeyStart . 'selected', $paymentKey);

        $method = 'klarna_payments';

        $input->setData('method', $method);
        $observer->setData('input', $input);
    }

    /**
     * Because of Klarna Payments's redirect, Magento does not send the order email.
     *
     * This method will trigger the email sending even though there is a redirect
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkoutSubmitAfterAllSendOrderEmail(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        if (!$order->getEmailSent() &&
            $order->getPayment()->getMethod() === 'klarna_payments' &&
            $order->getState() === Mage_Sales_Model_Order::STATE_PROCESSING) {

            $order->sendNewOrderEmail();
        }
    }

    /**
     * Clear Klarna Payment session variables when the checkout session is cleared
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkoutSessionClear(Varien_Event_Observer $observer)
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->setKlarnaPaymentsPayloadToken(null);
        $checkoutSession->setKlarnaPaymentsItemCheckToken(null);
    }

    /**
     * Update User-Agent with module version info
     *
     * @param $event
     */
    public function klarnaCoreClientUserAgentString($event)
    {
        $version = Mage::getConfig()->getModuleConfig('Klarna_Payments')->version;
        $versionObj = $event->getVersionStringObject();
        $verString = $versionObj->getVersionString();
        $verString .= ";Klarna_Payments_v{$version}";
        $versionObj->setVersionString($verString);
    }


    /**
     * validate order total for OSC checkout
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkOrderTotalForOsc(Varien_Event_Observer $observer)
    {
        $requestObject = $observer->getRequestObject();
        $request = $requestObject->getRequestBody();

        if (Mage::app()->getRequest()->getRouteName() === 'onestepcheckout') {
            $orderLineTotal = 0;
            foreach ($request['order_lines'] as $orderLine) {
                $orderLineTotal += $orderLine['total_amount'];
            }

            if ($orderLineTotal != $request['order_amount']) {
                $request['order_amount'] = $orderLineTotal;
            }

            $requestObject->setRequestBody($request);
        }
    }

    /**
     * record selected payment type
     *
     * @param Varien_Event_Observer $observer
     */
    public function recordPaymentType(Varien_Event_Observer $observer)
    {
        $klarnaKeyStart = 'klarna_payments_';
        $checkoutSession = Mage::getSingleton('checkout/session');
        $selectPaymentType = $checkoutSession->getData($klarnaKeyStart . 'selected');

        if ($selectPaymentType) {
            $order = $observer->getOrder();
            $order->getPayment()->setAdditionalInformation(
                'klarna_payment_type',
                $selectPaymentType
            );            
            $order->getPayment()->save();
        }
    }
}
