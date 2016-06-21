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
class Spletnisistemi_Activa_Block_Payment extends Mage_Core_Block_Abstract {
    protected function _toHtml() {
        $activaModel = Mage::getModel('activa/activa');
        $form = new Varien_Data_Form();

        $url = $activaModel->initPayment();
        $formUrl = $url['url'];

        $form->setAction($formUrl)
            ->setId('activa')
            ->setName('activa')
            ->setMethod('POST')
            ->setCharSet('utf-8')
            ->setUseContainer(true);

        // Add fields need for proper request
        $fieldset = $form->addFieldset('activa', array('legend' => ''));
        $fieldset->addField('error', 'hidden', array('name' => 'error', 'title' => 'error', 'label' => 'error', 'required' => true));

        $form->setUseContainer(true);
        $form->setValues(array('error' => $url['data']['msg']));

        $html = '';
        $html .= $this->__('SPLETNISISTEMI_ACTIVA_REDIRECTION_START');
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("activa").submit();</script>';
        $html .= '<button onclick="document.getElementById("activa").submit();" >' . $this->__("naprej") . '</button>';

        return $html;
    }
}