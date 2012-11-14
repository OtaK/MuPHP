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
     * @version 0.1dev
     * @changelog
     *      0.1dev : in progress
     */

    namespace TakPHPLib\WebserviceServer;
    require_once __DIR__.'/main.php';

    class apWsWrongAPIKeyException extends \Exception
    {
        public function __construct()
        {
            $this->message = "The supplied API Key is incorrect or does not exist!";
        }
    }

    /**
     * @package TakPHPLib
     * @subpackage Webservice Server
     * secureApWs is a subclass of apWs which implements most secure mecanisms in web API providers
     */
    abstract class secureApWs extends apWs
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
         * @throws apWsWrongAPIKeyException
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
            list($found) = \TakPHPLib\DB\dbMan::get_instance()->singleResQuery("
                SELECT id FROM users WHERE api_key = '%s'",
                array($this->_apiKey),
                MYSQLI_NUM
            );

            if (!$found)
                throw new apWsWrongAPIKeyException();

          /*  list($this->_privateKey) = \TakPHPLib\DB\dbMan::get_instance()->singleResQuery("
                SELECT user_private_key
                FROM user_keys
                WHERE api_key = '%s'",
                array($this->_apiKey)
            );
            if (!$this->_privateKey) return;

            // need unencrypted data
            $this->_inputData = \TakPHPLib\Crypt\cryptMan::decrypt($this->_data['hashedData'], \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_WS);*/
            $this->_uid = $found;
            $this->_inputData = &$this->_data['data'];
            return true;
        }



    }