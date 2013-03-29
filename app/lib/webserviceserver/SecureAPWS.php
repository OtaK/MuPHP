<?php

    /*
    * Copyright 2013 Mathieu "OtaK_" Amiot <m.amiot@otak-arts.com> http://mathieu-amiot.fr/
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
     * @package MuPHP
     * @subpackage Webservice Server
     * @author Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright Copyright (c) 2013, Mathieu AMIOT
     * @version 0.7
     * @changelog
     *      0.7 : Stable build, though no nonce and private/public key pair support
     *      0.1dev : in progress
     */

    namespace MuPHP\WebserviceServer;
    require_once __DIR__ . '/APWS.php';

    class SecureAPWSWrongAPIKeyException extends \Exception
    {
        public function __construct()
        {
            $this->message = "The supplied API Key is incorrect or does not exist!";
        }
    }

    /**
     * @package MuPHP
     * @subpackage Webservice Server
     * SecureAPWS is a subclass of APWS which implements most secure mecanisms in web API providers
     */
    abstract class SecureAPWS extends APWS
    {

        private
            //$_useNonce = false,
            $_apiKey,
            $_data,
            $_privateKey;

        protected
            $_uid;

        /**
         * @return string
         */
        /*public function getNonce()
        {
            if (!$this->_useNonce) return '';
            $nonce = "";
            //TODO to be implemented
            return $nonce;
        }*/

        /**
         * @return bool
         */
        public function _checkInputData()
        {
            $res = &$this->_getInputData();
            if (!isset($res) || $res === null) // Data presence
                return false;

            return (isset($res['data'], $res['apiKey']) /*&& (!$this->_useNonce || isset($res['nonce']))*/);
        }

        /**
         * @todo
         * @throws SecureAPWSWrongAPIKeyException
         * @return bool|void
         */
        public function gatherInputData()
        {
            parent::gatherInputData();
            $this->_data = $this->_inputData;

            /*if ($this->_useNonce)
            {
                //TODO
            }*/

            $this->_apiKey = $this->_data['apiKey'];
            // TODO Check api key
            list($found) = \MuPHP\DB\DBMan::get_instance()->singleResQuery("
                SELECT id FROM users WHERE api_key = '%s'",
                array($this->_apiKey),
                MYSQLI_NUM
            );

            if (!$found)
                self::quit(new SecureAPWSWrongAPIKeyException());

          /*  list($this->_privateKey) = \MuPHP\DB\DBMan::get_instance()->singleResQuery("
                SELECT user_private_key
                FROM user_keys
                WHERE api_key = '%s'",
                array($this->_apiKey)
            );
            if (!$this->_privateKey) return;

            // need unencrypted data
            $this->_inputData = \MuPHP\Crypt\CryptMan::decrypt($this->_data['hashedData'], \MuPHP\Crypt\CryptMan::CRYPTMAN_MODE_WS);*/
            $this->_uid = $found;
            $this->_inputData = &$this->_data['data'];
            return true;
        }



    }
