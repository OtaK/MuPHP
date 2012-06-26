<?php

    /**
     * @package TakPHPLib
     * @subpackage Webservice Server
     * @author Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright Copyright (c) 2012, Mathieu AMIOT
     * @version 0.7
     * @changelog
     *      0.7 : AJAX calls support implememented
     *      0.5 : first version that needs some testing
     *      0.1a : in progress
     */
    namespace TakPHPLib\WebserviceServer;

    class apWsWebserviceNotFoundException extends \Exception
    {
        public function __construct() { $this->message = "Supplied webservice doesn't exist !"; }
    }

    class apWsAjaxViolationAccess extends \Exception
    {
        public function __construct() { $this->message = "AJAX Violation Access"; }
    }

    class apWsActionMethodNotFound extends \Exception
    {
        public function __construct() { $this->message = "The requested action has not been implemented!"; }
    }

    class apWsBadModeSupplied extends \Exception
    {
        public function __construct() { $this->message = "The supplied mode doesn't exist!"; }
    }

    /**
     * @package TakPHPLib
     * @subpackage Webservice Server
     * apWs (All Purpose Webservice) is a modular class intended to manage all the possible needs
     * in webservice-driven applications such as iOS/Android apps, and coregistration services
     */
    abstract class apWs
    {
        protected
            $_inputData,
            $_outputData,
            $_mode,
            $_isAJAX;

        const
            APWS_POST = '_POST',
            APWS_GET = '_GET',
            APWS_PUT = 'http_response_header[\'PUT\']',
            APWS_DELETE = 'http_response_header[\'DELETE\']';

        /**
         * @static
         * @param string $name Classname to be instanciated
         * @return apWs
         * @throws apWsWebserviceNotFoundException
         */
        static public function factory($name)
        {
            if (file_exists(($fileName = dirname(__FILE__).'/includes/'.$name.'.php'))) include_once $fileName;
            else throw new apWsWebserviceNotFoundException();
            if (class_exists($name)) return new $name();
            else throw new apWsWebserviceNotFoundException();
        }

        /**
         * @param bool $asObject
         * @param bool $echo
         * @return string
         */
        protected function outputResult($asObject = false, $echo = true)
        {
            $res = json_encode($this->_outputData, ($asObject ? JSON_FORCE_OBJECT : null));
            if ($echo) echo $res;
            return $res;
        }

        /**
         * @return bool
         */
        protected function gatherInputData()
        {
            if (!isset(${$this->_mode})) return false;
            if ($this->_isAJAX
            && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'))
                self::quit(new apWsAjaxViolationAccess());
            $this->_inputData = ${$this->_mode};
            return true;
        }

        public function setMode($mode = self::APWS_POST) { $this->_mode = $mode; }
        public function getMode() { return $this->_mode; }
        public function isAJAX($val = null) { if ($val !== null && is_bool($val)) $this->_isAJAX = $val; return $this->_isAJAX; }

        /**
         * @param bool $asObject
         * @param bool $echo
         * @return string
         * @throws apWsBadModeSupplied
         */
        public function run($asObject = false, $echo = true)
        {
            if (!$this->gatherInputData())
                throw new apWsBadModeSupplied();
            $this->process();
            return $this->outputResult($asObject, $echo);
        }

        /**
         * Function in which the processing will take place, needs to be implemented by child classes
         * @abstract
         */
        abstract protected function process();

        /**
         * Quits the current webservice script with a JSON error
         * @static
         * @param \Exception $e
         */
        static public function quit(\Exception $e)
        {
            die(json_encode(array(
                'error' => true,
                'errorText' => $e->getMessage()
            )));
        }
    }
