<?php

    /*
    * Copyright 2012 Mathieu "OtaK_" Amiot <m.amiot@otak-arts.com> http://mathieu-amiot.fr/
    *
    * Licensed under the Apache License, Version 2.0 (the "License");
    * you may not use this file except in compliance with the License.
    * You may obtain a copy of the License at
    *
    *      http://www.apache.org/licenses/LICENSE-2.0
    *
    * Unless required by applicable law or agreed to in writing, software
    * distributed under the License is distributed on an "AS IS" BASIS,
    * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    * See the License for the specific language governing permissions and
    * limitations under the License.
    *
    */

    /**
     * @package TakPHPLib
     * @subpackage Webservice Server
     * @author Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright Copyright (c) 2012, Mathieu AMIOT
     * @version 1.1
     * @changelog
     *      1.1 : Added PUT, HEAD, DELETE full support, full RESTful conformance
     *      1.0 : stable version, fixed most bugs
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
            $_inputData = array(),
            $_outputData = array(),
            $_jsonOptions = JSON_FORCE_OBJECT,
            $_mode = self::APWS_GET,
            $_isAJAX = false;

        const
            APWS_POST = 0x01,
            APWS_GET = 0x02,
            APWS_PUT = 0x04,
            APWS_DELETE = 0x08,
            APWS_HEAD = 0x16;

        /**
         * Abstract ctor forcing children to implement it (configuration part)
         */
        abstract public function __construct();

        /**
         * @static
         * @param string $name Classname to be instanciated
         * @return apWs
         * @throws apWsWebserviceNotFoundException
         */
        static public function factory($name)
        {
            if (file_exists(($fileName = __DIR__.'/includes/'.$name.'.php'))) include_once $fileName;
            else throw new apWsWebserviceNotFoundException();
            $className = '\TakPHPLib\WebserviceServer\apWs\\'.$name;
            if (class_exists($className)) return new $className();
            else throw new apWsWebserviceNotFoundException();
        }

        /**
         * @param bool $echo
         * @return string
         */
        protected function outputResult($echo = true)
        {
            $res = json_encode($this->_outputData, $this->_jsonOptions);
            if ($echo) echo $res;
            return $res;
        }

        /**
         * @return bool
         */
        protected function gatherInputData()
        {
            if (!$this->_checkInputData()) return false;
            if ($this->_isAJAX
                && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'))
                self::quit(new apWsAjaxViolationAccess());
            $this->_inputData = &$this->_getInputData();
            return true;
        }

        /**
         * @return bool
         */
        protected function _checkInputData()
        {
            $res = &$this->_getInputData();
            return $res !== null && isset($res);
        }

        /**
         * @return array|null
         */
        protected function &_getInputData()
        {
            switch ($this->_mode)
            {
                case self::APWS_GET:
                case self::APWS_HEAD:
                    return $_GET;

                case self::APWS_POST: return $_POST;

                case self::APWS_DELETE:
                case self::APWS_PUT:
                    parse_str(file_get_contents('php://input'), $put);
                    return $put;

                default: return null;
            }
        }

        public function outputAsObject($val = true)
        {
            if ($val)
                $this->_jsonOptions |= JSON_FORCE_OBJECT;
            else
                $this->_jsonOptions &= ~JSON_FORCE_OBJECT;
        }

        public function jsonPrettyPrint($val = false)
        {
            if (!(PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >= 4)) return; // Incompatible

            if ($val)
                $this->_jsonOptions |= JSON_PRETTY_PRINT;
            else
                $this->_jsonOptions &= ~JSON_PRETTY_PRINT;
        }

        public function setMode($mode = self::APWS_POST) { $this->_mode = $mode; }
        public function getMode() { return $this->_mode; }
        public function isAJAX($val = null) { if ($val !== null && is_bool($val)) $this->_isAJAX = $val; return $this->_isAJAX; }

        /**
         * @param bool $echo
         * @return string
         * @throws apWsBadModeSupplied
         */
        public function run($echo = true)
        {
            header('Content-Type: application/json');
            if (!$this->gatherInputData())
                throw new apWsBadModeSupplied();
            $this->process();
            return $this->outputResult($echo);
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
         * @param apWs       $context
         */
        static public function quit(\Exception $e, apWs &$context = null)
        {
            $dieData = array(
                'error' => true,
                'errorText' => $e->getMessage()
            );
            if (DEBUG && $context)
                $dieData['inputData'] = $context->_inputData;
            die(json_encode($dieData));
        }
    }

    include_once __DIR__.'/secureApWs.php';
