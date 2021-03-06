<?php

/**
 * Copyright Spletni sistemi, (c) 2009.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * @category   Spletnisistemi
 * @package    Spletnisistemi_Activa
 * @copyright  Copyright (c) 2009 Spletni sistemi (http://spletnisistemi.si)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Spletnisistemi_Activa_Model_Activa extends Mage_Payment_Model_Method_Abstract {
    protected $_code = 'activa';

    protected $_formBlockType = 'activa/form';
    protected $_paymentMethod = 'activa';
    protected $_allowCurrencyCode = array('EUR');

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;

    /**
     * create the block form
     *
     * @param   String $name
     * @return  unknown
     */
    public function createFormBlock($name) {
        $block = $this->getLayout()->createBlock('activa_form', $name)
            ->setMethod('activa_form')
            ->setPayment($this->getPayment())
            ->setTemplate('activa/form.phtml');

        return $block;
    }

    /**
     * this function gets called if the user clicks "place order"
     *
     * @return void
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('activa/activa/redirect');
    }

    /**
     * this function gets called after init redirection from place order"
     *
     * @return void
     */
    public function getActivaUrl() {
        return Mage::getUrl('activa/activa/payment');
    }

    public function validate() {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();

        if ($currency_code != null and !in_array($currency_code, $this->_allowCurrencyCode)) {
            Mage::throwException(sprintf(Mage::helper('activa')->__('SPLETNISISTEMI_ACTIVA_CURRENCY_CODE_ERROR'), $currency_code));
        }

        return $this;
    }

    /**
     * Get the Singleton of current Quote
     *
     * @return  Singleton Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    /*validate the currency code is avaialable to use for activa or not*/

    /**
     * Get the Singleton of current Checkout Session
     *
     * @return  Singleton Checkout Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    // Unique id is also real order id

    public function makePaymentId() {
        $session = $this->getCheckout();

        return $session->getLastRealOrderId();
    }

    /**
     * Return redirect block type
     *
     * @return string
     */
    public function getRedirectBlockType() {
        return $this->_redirectBlockType;
    }

    /**
     * Return payment method type string
     *
     * @return string
     */
    public function getPaymentMethodType() {
        return $this->_paymentMethod;
    }

    public function initPayment() {
        $session = $this->getCheckout();

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());

        $order->setState(Mage_Sales_Model_Order::STATE_NEW);
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('activa')->__('SPLETNISISTEMI_ACTIVA_CUSTOMER_REDIRECT_TO_ACTIVA'));
        $order->save();

        $session->unsQuoteId();
        $session->getQuote()->setIsActive(false)->save();

        $payment = Mage::getModel('activa/activa_payment');
        $payment = $payment->initPayment($order->getBillingAddress(), $session->getLastRealOrderId()); // return redirect url

        return $payment;
    }

    // DB calls

    public function generateRequestData() {
        return $this->createRequestData();
    }

    protected function createRequestData() {
        // Set urls based on base url
        $baseURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $responseURL = "{$baseURL}activa/activa/update/";
        $errorURL = "{$baseURL}activa/activa/error/";

        // Get total amount of order
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());

        $customerData = $this->getCheckout()->getQuote()->getCustomer();
        $customerPrimaryBillingAddress = $order->getBillingAddress();
        $customerShippingAddress = $this->getCheckout()->getQuote()->getShippingAddress();

        // Get data, smt from config, smt from session, order
        $data = array(
            // Payment specific data
            'vrstaTran'     => $this->getConfigData('paymenttype'), // Tip transakcije (1-9). 1 -> Purchase, 4 -> Authorization
            'valuta'        => $this->getConfigData('currencycode'), //978,
            'vrstaJezika'   => $this->getConfigData('languagecode'), //'SI',
            'responseURL'   => $responseURL, //'https://www.cha.si/activa/PaymentResponse.php',
            'errorURL'      => $errorURL, //'http://www.cha.si/activa/error.php',
            'payID'         => $this->getCheckout()->getLastRealOrderId(),
            'resourcePath'  => $this->getConfigData('resourcepath'), //'/home/chasi/public_html/activa/resource/',
            'terminalAlias' => $this->getConfigData('terminalalias'), // 'Cha Terminal',
            'vrednost'      => $this->formatAmount($order->getBaseGrandTotal()),

            // Customer data
            'usr_id'        => $customerData->getData('entity_id'),
            'usr_ime'       => $customerPrimaryBillingAddress->getData('firstname'),
            'usr_priimek'   => $customerPrimaryBillingAddress->getData('lastname'),
            'usr_eposta'    => $customerData->getData('email'),
            'usr_ulica'     => $customerPrimaryBillingAddress->getData('street'),
            'usr_mesto'     => $customerPrimaryBillingAddress->getData('postcode') . ' ' . $customerPrimaryBillingAddress->getData('city'),
        );

        return $data;
    }

    protected function formatAmount($amount) {
        return Mage::helper('activa')->formatAmount($amount);
    }

    // Wrappers around DB calls - additional functionality
    // Process which does all the magic for payment start

    public function updateRequest() {
        // Empty $errText - success otherwise error
        return $this->captureRequest();
    }

    protected function captureRequest() {
        // Set urls based on base url
        $baseURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $statusURL = "{$baseURL}activa/activa/status/";
        $errorURL = "{$baseURL}activa/activa/error/";

        // Naloži class order
        $nar = Mage::getModel('activa/activa_order');

        // Preverimo, ce je prislo pri placilu, do kake napake.
        $errMsg = false;
        $errText = false;

        if (Mage::helper('activa')->getPost('Error') != '') {
            $errMsg = Mage::helper('activa')->getPost('Error');
        }

        if ($errMsg != '') {
            // Zajamemo podatke o stanju placila, ki jih sistem CGw poslje z metodo POST zato, da lahko zapisemo tip napake.
            $paymentid = Mage::helper('activa')->getPost('paymentid'); // 6728256381693450
            $errText = Mage::helper('activa')->getPost('ErrorText');

            // Preberemo narocilo iz baze.
            if ($nar->fetch($paymentid) != 0) {
                $errText = "REDIRECT={$errorURL}?ErrorTx=Narocilo+se+ni+izvedlo.+Tezave+pri+branju+iz+baze+narocil.";
            } else {
                $nar->setPaymentID($paymentid);
                $nar->setErrMsg($errMsg);
                $nar->setErrText($errText);

                // Osvezimo narocilo v bazi narocil.
                if ($nar->updateErr() != 0) {
                    // Izvede se preusmeritev na trgovcev URL o obvestilu o napaki.
                    $errText = "REDIRECT={$errorURL}?ErrorTx=Narocilo+se+ni+izvedlo.+Tezave+pri+osvezevanju+iz+baze+narocil.";
                } else {
                    $errText = "REDIRECT={$errorURL}?ErrorTx={$errText}";
                }
            }
        } else {
            // Zajamemo podatke o stanju placila, ki jih sistem CGw poslje z metodo POST. To se ne pomeni, da je bilo
            // placilo odobreno.
            $paymentid = Mage::helper('activa')->getPost('paymentid'); // 6728256381693450
            $result = (Mage::helper('activa')->getPost('result') ? Mage::helper('activa')->getPost('result') : '');
            $responsecode = (Mage::helper('activa')->getPost('responsecode') ? Mage::helper('activa')->getPost('responsecode') : '');
            $postdate = (Mage::helper('activa')->getPost('postdate') ? Mage::helper('activa')->getPost('postdate') : '');
            $udf1 = (Mage::helper('activa')->getPost('udf1') ? Mage::helper('activa')->getPost('udf1') : '');
            $udf2 = (Mage::helper('activa')->getPost('udf2') ? Mage::helper('activa')->getPost('udf2') : '');
            $udf3 = (Mage::helper('activa')->getPost('udf3') ? Mage::helper('activa')->getPost('udf3') : '');
            $udf4 = (Mage::helper('activa')->getPost('udf4') ? Mage::helper('activa')->getPost('udf4') : '');
            $udf5 = (Mage::helper('activa')->getPost('udf5') ? Mage::helper('activa')->getPost('udf5') : '');
            $tranid = (Mage::helper('activa')->getPost('tranid') ? Mage::helper('activa')->getPost('tranid') : '');
            $auth = (Mage::helper('activa')->getPost('auth') ? Mage::helper('activa')->getPost('auth') : '');
            $trackid = (Mage::helper('activa')->getPost('trackid') ? Mage::helper('activa')->getPost('trackid') : '');
            $reference = (Mage::helper('activa')->getPost('ref') ? Mage::helper('activa')->getPost('ref') : '');

            // Preberemo narocilo iz baze.
            if ($nar->fetch($paymentid) != 0) {
                $errText = "REDIRECT={$errorURL}?ErrorTx=Narocilo+se+ni+izvedlo.+Tezave+pri+branju+iz+baze+narocil.";
            } else {
                // Pripravimo podatke za osvezitev narocila v bazi.
                // V primeru Authorization bo rezultate Approved ali Not Approved.
                // V primeru Purchase bo rezultate Captured ali Not Captured.
                $nar->setResult($result);
                $nar->setAuth($auth);
                $nar->setRef($reference);
                $nar->setTranID($tranid);
                $nar->setPostDate($postdate);
                $nar->setTrackID($trackid);
                $nar->setudf1($udf1);
                $nar->setudf2($udf2);
                $nar->setudf3($udf3);
                $nar->setudf4($udf4);
                $nar->setudf5($udf5);
                $nar->setResponseCode($responsecode);
                $nar->setPaymentID($paymentid);

                // Osvezimo narocilo v bazi narocil.
                if ($nar->update() != 0) {
                    // Izvede se preusmeritev na trgovcev URL o obvestilu o napaki.
                    $errText = "REDIRECT={$errorURL}?ErrorTx=Narocilo+se+ni+izvedlo.+Tezave+pri+osvezevanju+iz+baze+narocil.";
                } else {
                    $errText = "REDIRECT={$statusURL}paymentid/{$paymentid}";
                }
            }
        }

        return $errText;
    }

    public function completeRequest() {
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());

        // Empty $errText - success otherwise error
        $status = $this->statusRequest();

        // Success
        if ($status === true) {
            if ($order->canInvoice()) { // potrjena activa - naredim invoice
                $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, sprintf(Mage::helper('activa')->__('SPLETNISISTEMI_ACTIVA_APPROVED_INVOICE_CREATED'), ''));
            } else {
                // ne morem narest invoice - spremenim v pending

                $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('activa')->__('SPLETNISISTEMI_ACTIVA_APPROVED_INVOICE_FAILED'));
            }
            // Error
        } else {
            $order->setState(Mage_Sales_Model_Order::STATE_HOLDED);
            $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('activa')->__('SPLETNISISTEMI_ACTIVA_REJECTED'));
        }

        return ($status === true) ? true : $status;
    }

    protected function statusRequest() {
        // Zajamemo paymentid, ki ga sistem CGw posreduje z metodo GET.
        $payID = Mage::helper('activa')->getRequest()->getParam('paymentid');
        $msg = "Nepravilen+status.";

        // Ce podatka o paymentid ne dobimo, je prislo do sistemske napake.
        if (empty($payID)) {
            $msg = "Napaka+pri+prenosu+PaymentIDja+transakcije.";
        } else {
            // Naloži class order
            $nar = Mage::getModel('activa/activa_order');

            if ($nar->fetch($payID) != 0) {
                $msg = "Tezave+pri+branju+podatkov+o+narocilu+iz+baze+narocil.";
            } elseif (($nar->getResult() == 'CAPTURED') || ($nar->getResult() == 'APPROVED')) {
                $msg = true;
            } else {
                $msg = "Transakcija+ni+uspela.+Prosim+poizkusite+ponovno.";
            }
        }

        return $msg;
    }

    protected function dump($var, $withVarTypes = false) {
        if ($withVarTypes) {
            ob_start();
            var_dump($var);
            $var = ob_get_contents();
            ob_end_clean();
        }
        // Instead of returning empty string on boolean false and true, return (bool)true string
        $var = ($var === true) ? '(bool)true' : $var;
        $var = ($var === false) ? '(bool)false' : $var;
        echo '<pre style="background: #ddd; border: 1px solid black; margin: 5px; text-align: left; padding: 10px; color: #000; clear: both;">' . htmlspecialchars($withVarTypes ? $var : print_r($var, true)) . '</pre>';
    }
}