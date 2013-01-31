<?php
    namespace MuPHP\WebserviceServer\Endpoint;
    require_once __DIR__ . '/../lib/db/dbMan.php';
    require_once __DIR__ . '/../lib/crypto/hashMan.php';

    class auth extends \MuPHP\WebserviceServer\apWs
    {
        /**
         * Abstract ctor forcing children to implement it (configuration part)
         */
        public function __construct()
        {
            $this->_isAJAX = false;
            $this->_mode = self::APWS_GET;
        }

        /**
         * Function in which the processing will take place, needs to be implemented by child classes
         */
        protected function process()
        {
            $this->_inputData['works'] = true;
        }
    }