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
     */
    namespace TakPHPLib\Accounts;
    require_once dirname(__FILE__) . '/../cfg/define.php';
    require_once dirname(__FILE__) . '/cryptMan.php';

    /**
     * @package    TakPHPLib
     * @subpackage Accounts
     *             Class to manage an user, plus password encryption inside cookies
     */
    class userMan
    {
        protected
            $user_id,
            $user_name,
            $user_email,
            $encrypted_passwd,
            $auth_level,
            $use_cookies,
            $loggedIn;

        /**
         * Ctor
         * @param int    $uid       DB's UID (User ID)
         * @param string $email     User's email address
         * @param string $passwd    Password *not* encrypted (yet)
         * @param string $authlevel User rank
         */
        public function __construct($uid = -1, $email = '', $passwd = '', $authlevel = 'USER')
        {
            $this->loggedIn = false;
            if ($uid !== -1)
            {
                $this->user_id          = $uid;
                $this->user_email       = $email;
                $this->auth_level       = $authlevel;
                $this->encrypted_passwd = \TakPHPLib\Crypt\cryptMan::encrypt($passwd);
            }
            else
                $this->loadFromCookies();
        }

        /**
         * Changes the password
         * @param string $newClearPass
         * @return void
         */
        public function setPassword($newClearPass)
        {
            $this->encrypted_passwd = \TakPHPLib\Crypt\cryptMan::encrypt($newClearPass, \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_DATA);
        }

        /* Getters and setters */
        public function getUserId()
        {
            return $this->user_id;
        }

        public function getUserName()
        {
            return $this->user_name;
        }

        public function setUserName($val)
        {
            $this->user_name = $val;
        }

        public function getEmail()
        {
            return $this->user_email;
        }

        public function getEncryptedPasswd()
        {
            return $this->encrypted_passwd;
        }

        public function setEncryptedPasswd($val)
        {
            $this->encrypted_passwd = $val;
        }

        public function getAuthLevel()
        {
            return $this->auth_level;
        }

        public function isAdmin()
        {
            return $this->auth_level != 'USER';
        }

        public function userLoggedIn()
        {
            return $this->loggedIn;
        }

        public function isUsingCookies()
        {
            return $this->use_cookies;
        }

        public static function loggedIn()
        {
            return isset($_SESSION['USER_DATA']) && $_SESSION['USER_DATA']->userLoggedIn();
        }

        /**
         * Gets the current user logged in or null on fail
         * @static
         * @return userMan|null
         */
        public static function currentUser()
        {
            return (self::loggedIn() ? $_SESSION['USER_DATA'] : (@session_start() && self::loggedIn() ? $_SESSION['USER_DATA'] : null));
        }

        /**
         * Starts a session and sets the current object available globally in the session
         * @param bool $cookies defines if current session will use cookies
         * @return void
         */
        public function login($cookies)
        {
            $this->use_cookies = $cookies;
            @session_start();
            $_SESSION['USER_DATA'] = &$this;
            $this->loggedIn        = true;
            if ($this->use_cookies)
                $this->saveToCookies();
            else
                $this->deleteCookies();
        }

        /**
         * Stops and destroy a session -- logouts
         * @return void
         */
        public function logout()
        {
            @session_unset();
            @session_destroy();
            $this->loggedIn = false;
            if ($this->use_cookies)
                $this->saveToCookies();
            else
                $this->deleteCookies();
        }

        /**
         * Sets a cookie with the class in its current state, with an expiration date of 1 year.
         * Secure against XSS too. HTTPS maybe later, disabled for now.
         * @return bool
         */
        public function saveToCookies()
        {
            $classData             = \TakPHPLib\Crypt\cryptMan::encrypt(serialize($this), \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_DATA);
            $_COOKIE['LOGIN_DATA'] = $classData;
            return setcookie('LOGIN_DATA', $classData, time() + 31536000, SITE_PATH, null, false, true);
        }

        /**
         * Deletes the cookie by setting an empty cookie that already expired one hour ago => browser will delete it
         * @return void
         */
        public function deleteCookies()
        {
            if (!isset($_COOKIE['LOGIN_DATA'])) return;
            setcookie('LOGIN_DATA', false, time() - 3600);
            unset($_COOKIE['LOGIN_DATA']);
        }

        /**
         * Reloads the class from the state saved in the cookies.
         * With additionnal check if the class is the right name for security reasons.
         * @return bool
         */
        public function loadFromCookies()
        {
            if (!isset($_COOKIE['LOGIN_DATA'])) return false;
            $obj = unserialize(\TakPHPLib\Crypt\cryptMan::decrypt($_COOKIE['LOGIN_DATA'], \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_DATA));
            if (get_class($obj) != 'userMan') return false;
            /** @var userMan $obj */
            $this->auth_level       = $obj->auth_level;
            $this->encrypted_passwd = $obj->encrypted_passwd;
            $this->user_email       = $obj->user_email;
            $this->user_id          = $obj->user_id;
            $this->user_name        = $obj->user_name;
            $this->loggedIn         = $obj->loggedIn;
            $data                   = \TakPHPLib\DB\dbMan::get_instance()->singleResQuery("SELECT * FROM users WHERE user_id = '%d'", array($this->user_id));
            if ($this->matchDbData($data))
            {
                if ($this->loggedIn)
                    $this->login(true);
            }
            else
                $this->resetToDefaults();
            return true;
        }

        /**
         * Resets current object to default values in case of an unauthorized access
         * @return void
         */
        protected function resetToDefaults()
        {
            $this->auth_level       = 'USER';
            $this->encrypted_passwd = '';
            $this->user_id          = -1;
            $this->user_name        = '';
            $this->user_email       = '';
            $this->loggedIn         = false;
        }

        /**
         * Loads an user from DB to Class
         * @param array $data db result of querying the user
         * @return bool
         */
        public function loadFromDbData(array $data)
        {
            if (!isset($data['user_id'], $data['user_email'], $data['user_pwd'], $data['user_status'])) return false;

            $clearPasswd            = \TakPHPLib\Crypt\cryptMan::decrypt($data['user_pwd'], \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_DB);
            $this->encrypted_passwd = \TakPHPLib\Crypt\cryptMan::encrypt($clearPasswd, \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_DATA);
            $this->auth_level       = $data['user_status'];
            $this->user_email       = $data['user_email'];
            $this->user_id          = $data['user_id'];
            $this->user_name        = $data['user_display_name'];

            return true;
        }

        /**
         * Checks if the current data is matching the one given by the MySQLi_Result
         * @param array $data
         * @return bool
         */
        public function matchDbData(array $data)
        {
            if (!isset($data['user_id'], $data['user_email'], $data['user_pwd'], $data['user_status'], $data['user_enabled'])) return false;

            $clearDbPasswd   = \TakPHPLib\Crypt\cryptMan::decrypt($data['user_pwd'], \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_DB);
            $clearThisPasswd = \TakPHPLib\Crypt\cryptMan::decrypt($this->encrypted_passwd, \TakPHPLib\Crypt\cryptMan::CRYPTMAN_MODE_DATA);

            $result = (
                $data['user_id'] == $this->user_id
                    && $data['user_email'] == $this->user_email
                    && $data['user_status'] == $this->auth_level
                    && $clearDbPasswd == $clearThisPasswd
                    && $data['user_enabled'] == 'ENABLED'
            );

            return $result;
        }
    }
