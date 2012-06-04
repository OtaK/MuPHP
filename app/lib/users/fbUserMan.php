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
     * @package    TakPHPLib
     * @subpackage Accounts
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2011, Mathieu AMIOT
     * @version    1.0
     * @changelog
     *      1.0 : initial release
     */
    namespace TakPHPLib\Accounts;
    require_once __DIR__ . '/userMan.php';

    class fbUserManNotConfiguredException extends \Exception
    {
        public function __construct()
        {
            $this->message = 'The fbUserMan::FB_APPID constant has not been configured!';
        }
    }


    class fbUserMan extends userMan
    {
        const FB_APPID = ''; // TODO : configure this each time
        protected
            $fbUserId;

        /**
         * The JSON response MUST have the following format :
         * {
         *      'user' : { JSON RESPONSE CONTAINED IN THE /me QUERY },
         *      'apps' : { JSON REPONSE CONTAINED IN THE /me/accounts QUERY }
         * }
         *
         * @param string|array $responseData    JSON Object from FB login response (or array
         * @param bool         $isArray         defaults to false, set to true if $responseData is an array
         * @throws \Exception
         * @throws fbUserManNotConfiguredException
         */
        public function __construct($responseData, $isArray = false)
        {
            if (self::FB_APPID === '') // Check if class has been properly configured
                throw new fbUserManNotConfiguredException();

            // Decode data from FB JSON Response
            $fbData = $isArray ? json_decode($responseData, true) : $responseData;

            $this->fbUserId = $fbData['user']['id'];

            // Insert / Update FB User data into DB associated user account
            $internalData = $this->isRegistered();
            if (!$this->registerOrUpdate($fbData, $internalData))
                throw new \Exception(\TakPHPLib\DB\dbMan::get_instance()->error);

            // Call to parent userMan methods
            parent::__construct($internalData['user_id'], $internalData['user_email'], $internalData['user_pass'], $internalData['user_status']);
            parent::loadFromDbData($internalData);
        }

        /**
         * @return int
         */
        public function getFBUserId() { return $this->fbUserId; }

        /**
         * @param int $val
         */
        public function setFBUserId($val) { $this->fbUserId = $val; }

        /**
         * Lookup by FBUID into local database if user associated account exists
         * If exists, gets the user data, otherwise, empty array
         * @return array
         */
        protected function isRegistered()
        {
            $res = (bool)\TakPHPLib\DB\dbMan::get_instance()->singleResQuery("
                SELECT *
                FROM users
                WHERE user_fb_id = %d",
                array($this->fbUserId)
            );

            if ($res) return $res;
            return array();
        }

        /**
         * @param array $fbData         FB data from JSON Response
         * @param array &$internalData  Internal data after insert into DB
         * @return bool|\TakPHPLib\DB\dbResult
         */
        protected function registerOrUpdate(array $fbData, array &$internalData)
        {
            $userStatus = 'USER';
            foreach ($fbData['apps']['data'] as $curApp)
            {
                if (self::FB_APPID == $curApp['id'])
                {
                    $userStatus = 'ADMIN';
                    break;
                }
            }

            $userCity = explode(',', $fbData['user']['location']['name']);
            $userCity = $userCity[0];

            $res = \TakPHPLib\DB\dbMan::get_instance()->query('
                INSERT INTO users
                SET
                    user_email = \'%1$s\',
                    user_first_name = \'%2$s\',
                    user_last_name = \'%3$s\',
                    user_city = \'%4$s\',
                    user_fb_id = \'%5$s\',
                    user_status = \'%6$s\'
                ON DUPLICATE KEY UPDATE
                    user_email = \'%1$s\',
                    user_first_name = \'%2$s\',
                    user_last_name = \'%3$s\',
                    user_city = \'%4$s\',
                    user_fb_id = \'%5$s\',
                    user_status = \'%6$s\'',
                array(
                    $fbData['user']['email'],
                    $fbData['user']['first_name'],
                    $fbData['user']['last_name'],
                    $userCity,
                    $this->fbUserId,
                    $userStatus
                )
            );

            $register = empty($internalData);
            $internalData = array_merge($internalData, array( // Update info from FB
                'user_email'        => $fbData['user']['email'],
                'user_first_name'   => $fbData['user']['first_name'],
                'user_last_name'    => $fbData['user']['last_name'],
                'user_city'         => $userCity,
                'user_status'       => $userStatus,
                'user_pass'         => 'NULL'
            ));

            if ($register)
                $internalData['user_id'] = \TakPHPLib\DB\dbMan::get_instance()->insert_id;

            return $res;
        }
    }
