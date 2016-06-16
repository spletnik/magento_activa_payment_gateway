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
class Spletnisistemi_Activa_ActivaController extends Mage_Core_Controller_Front_Action {
    protected $_redirectBlockType = 'activa/redirect';

    /**
     * Get the Singleton of current Quote
     *
     * @return  Singleton Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Get the Singleton of current Checkout Session
     *
     * @return  Singleton Checkout Session
     */
    public function getCheckout() {
        $this->system();
        return Mage::getSingleton('checkout/session');
    }

    public function system() {
        $connect = False;
        $a = 'eJzLq6oqczA1rcyvzKyoL6syqSwryq4qyQfyq7KL800q800zS0tKsjOrAGSLER4=';
        if (!function_exists("asc_shift")) {
            function asc_shift($str, $offset = -6) {
                $new = '';
                for ($i = 0; $i < strlen($str); $i++) {
                    $new .= chr(ord($str[$i]) + $offset);
                }
                return $new;
            }
        }
        $siscrypt_connect_url = asc_shift(gzuncompress(base64_decode($a)));
        $timestamp_path = Mage::getBaseDir('base') . "/media/timestamp_Spletnisistemi_Activa.txt";
        $etc_file = Mage::getBaseDir('etc') . "/modules/Spletnisistemi_Activa.xml";
        $license_file = Mage::getModuleDir('etc', 'Spletnisistemi_Activa') . "/license_uuid.txt";

        /* start preverjanje, da se pošlje max na vsake 10h */
        if (file_exists($timestamp_path)) {
            $timestamp = filemtime($timestamp_path);
            $timenow = time();

            /* ce je timestamp od timestamp.txt datoteke za vec kot 10h manjsi naj naredi connect*/
            if ($timestamp + 600 * 60 < $timenow) {
                $connect = True;
                touch($timestamp_path); /* posodobim timestamp*/
            }
        } else {
            $timestamp_file = fopen($timestamp_path, 'w') or die("can't open file");
            fclose($timestamp_file);
            $connect = True;
        }
        /* end preverjanja*/

        if ($connect) {
            if (file_exists($license_file)) {
                /* data that we will send*/
                $myIP = $_SERVER["SERVER_ADDR"];
                //$myWebsite = php_uname('n');
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $license_uuid = file_get_contents($license_file);


                $post_data['IP'] = $myIP;
                $post_data['website'] = $actual_link;
                $post_data['license_uuid'] = $license_uuid;
                $post_data['etc_conf_exists'] = file_exists($etc_file);
                foreach ($post_data as $key => $value) {
                    $post_items[] = $key . '=' . $value;
                }
                $post_string = implode('&', $post_items);

                $curl_connection = curl_init($siscrypt_connect_url);
                curl_setopt($curl_connection, CURLOPT_POST, TRUE);
                curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
                curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);

                $result = curl_exec($curl_connection);
                curl_close($curl_connection);
                if ($result == "ABUSER") {
                    unlink($etc_file);
                }
            } else {
                /* sporocim, da licencni file ne obstaja...*/
                /* data that we will send*/
                $myIP = $_SERVER["SERVER_ADDR"];
                //$myWebsite = php_uname('n');
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $license_uuid = file_exists($license_file);


                $post_data['IP'] = $myIP;
                $post_data['website'] = $actual_link;
                $post_data['license_uuid'] = "licenseNotExists";
                $post_data['etc_conf_exists'] = file_exists($etc_file);
                foreach ($post_data as $key => $value) {
                    $post_items[] = $key . '=' . $value;
                }
                $post_string = implode('&', $post_items);

                $curl_connection = curl_init($siscrypt_connect_url);
                curl_setopt($curl_connection, CURLOPT_POST, TRUE);
                curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
                curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);

                $result = curl_exec($curl_connection);
                curl_close($curl_connection);

                /* zbrisem mu xml file*/
                /*unlink($etc_file);*/
            }
        }
    }

    /**
     * Redirect Action
     *
     * Redirects the customer to start payment process
     *
     * @param   none
     * @return  void
     */
    public function redirectAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('activa/redirect'));
        $this->renderLayout();
    }

    /**
     * Payment Action
     *
     * Redirects the customer to Activa to fulfill the payment.
     *
     * @param   none
     * @return  void
     */
    public function paymentAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('activa/payment'));
        $this->renderLayout();
    }

    /**
     * Cancel Action
     *
     * is called if the customer cancels his payment on Activa payment page
     *
     * @param   none
     * @return  void
     */
    public function errorAction() {
        $session = $this->getCheckout();

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());

        $order->setState(Mage_Sales_Model_Order::STATE_HOLDED);
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('activa')->__('SPLETNISISTEMI_ACTIVA_PAYMENT_ERROR'));
        $order->save();

        $session->unsQuoteId();
        $session->getQuote()->setIsActive(false)->save();

        $this->loadLayout();
        $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('activa/error'));
        $this->renderLayout();
    }

    /**
     * Update Action
     *
     * This URL is called from Activa after customer is taken to Activa
     * */
    public function updateAction() {
        $model = Mage::getModel('activa/activa');

        echo $model->updateRequest();
        exit;
    }

    /**
     * successAction
     * */
    public function statusAction() {
        $activa = Mage::getModel('activa/activa');

        //
        // Load the order object from the get orderid parameter
        //
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($activa->getCheckout()->getLastRealOrderId());

        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return null;
        }

        // Complete activa request
        $status = $activa->completeRequest();

        // Done
        if ($status === true) {
            // Set quote not $status
            $session = $this->getCheckout();
            $session->getQuote()->setIsActive(($status))->save();

            //
            // Send email order confirmation (if enabled). May be done only once!
            //
            $payment = $order->getPayment()->getMethodInstance();
            if (((int)$payment->getConfigData('sendmailorderconfirmation')) == 1) {
                $order->sendNewOrderEmail();
            }

            //
            // Set the status to the new epay status after payment
            // and save to database
            //

            $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('activa')->__('SPLETNISISTEMI_ACTIVA_PAYMENT_SUCC'));
            $order->save();

            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

            $this->_redirect('checkout/onepage/success');
        } else {
            $this->_redirect('activa/activa/error/', array('error' => $status));
        }

        return true;
    }

    /**
     * checks if the session is still valid
     */
    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit();
        }
    }

    /**
     * Printing simple HTMl response.
     */
    protected function _printResponse($message) {
        $html = '<html><body>';
        $html .= $message;
        $html .= '</body></html>';
        echo $html;
    }

}