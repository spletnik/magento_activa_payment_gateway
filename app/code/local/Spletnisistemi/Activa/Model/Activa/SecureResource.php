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

class Spletnisistemi_Activa_Model_Activa_SecureResource {
    protected $strResourcePath;
    protected $strAlias;
    protected $termID;
    protected $password;
    protected $passwordHash;
    protected $port;
    protected $context;
    protected $webAddress;
    protected $error;
    protected $bDebugOn;
    protected $debugMsg;

    function __construct() {
        $this->strResourcePath = "";
        $this->strAlias = "";
        $this->termID = "";
        $this->password = "";
        $this->passwordHash = "";
        $this->port = "";
        $this->context = "";
        $this->webAddress = "";
        $this->error = "";
        $this->bDebugOn = true;
    }

    // Metoda prebere podatke iz resource datoteke.
    function getSecureSettings() {
        if ($this->strResourcePath == "") {
            $this->error = "No resource path specified.";
            return false;
        }

        if (!file_exists($this->getResourcePath().'resource.xml')) {
            $this->error = "Unable to open resource file (resource.xml)!";
            return false;
        }

        $strData = file_get_contents($this->getResourcePath().'resource.xml');

        if($strData == "") {
            return false;
        }

        return $this->parseSettings($strData);
    }

    function getResourcePath()
    {
        return $this->strResourcePath;
    }

    function setResourcePath($val)
    {
        $this->strResourcePath = $val;
    }


    // Niz pridobljen iz zacasne zip datoteke razparsamo.
    function parseSettings($settings) {

        if ($this->bDebugOn)
        $this->addDebugMessage("Parsing Settings.");
        $begin = strpos($settings, "<id>") + strlen("<id>");
        $end = strpos($settings, "</id>");
        if ($begin === false || $end === false)
        {
            $this->error = "Error parsing internal settings file.";
            return false;
        }

        $this->setTermID(substr($settings, $begin, $end-$begin));

        $begin = strpos($settings, "<password>") + strlen("<password>");
        $end = strpos($settings, "</password>");
        if ($begin === false || $end === false)
        {
            $this->error = "Error parsing internal settings file.";
            return false;
        }
        $this->setPassword(substr($settings, $begin, $end-$begin));
        
        $begin = strpos($settings, "<passwordhash>") + strlen("<passwordhash>");
        $end = strpos($settings, "</passwordhash>");
        if ($begin === false || $end === false)
        {
            $this->error = "Error parsing internal settings file.";
            return false;
        }
        $this->setPasswordHash(substr($settings, $begin, $end-$begin));

        $begin = strpos($settings, "<webaddress>") + strlen("<webaddress>");
        $end = strpos($settings, "</webaddress>");
        if ($begin === false || $end === false)
        {
            $this->error = "Error parsing internal settings file.";
            return false;
        }
        $this->setWebAddress(substr($settings, $begin, $end-$begin));

        $begin = strpos($settings, "<port>") + strlen("<port>");
        $end = strpos($settings, "</port>");
        if ($begin === false || $end === false)
        {
            $this->error = "Error parsing internal settings file.";
            return false;
        }
        $this->setPort(substr($settings, $begin, $end-$begin));

        $begin = strpos($settings, "<context>") + strlen("<context>");
        $end = strpos($settings, "</context>");
        if ($begin === false || $end === false)
        {
            $this->error = "Error parsing internal settings file.";
            return false;
        }
        $this->setContext(substr($settings, $begin, $end-$begin));
        return true;
    }


    function isDebugOn()
    {
        return $this->bDebugOn;
    }

    function setDebugOn($val)
    {
        $this->bDebugOn = $val;
    }

    function addDebugMessage($val)
    {
        if($this->bDebugOn)
        $this->debugMsg .= $val;
    }

    function getDebugMsg()
    {
        return $this->debugMsg;
    }


    function getAlias()
    {
        return $this->strAlias;
    }

    function setAlias($val)
    {
        $this->strAlias = $val;
    }

    function getContext()
    {
        return $this->context;
    }

    function setContext($val)
    {
        $this->context = $val;
    }

    function getPassword()
    {
        return $this->password;
    }

    function setPassword($val)
    {
        $this->password = $val;
    }

    function getPasswordHash()
    {
        return $this->passwordHash;
    }

    function setPasswordHash($val)
    {
        $this->passwordHash = $val;
    }

    function getPort()
    {
        return $this->port;
    }

    function setPort($val)
    {
        $this->port = $val;
    }

    function getTermID()
    {
        return $this->termID;
    }

    function setTermID($val)
    {
        $this->termID = $val;
    }

    function getWebAddress()
    {
        return $this->webAddress;
    }

    function setWebAddress($val)
    {
        $this->webAddress = $val;
    }

    function getError() {
        return $this->error;
    }
}