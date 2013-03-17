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
	 * @subpackage CookieFav
	 * @author Mathieu AMIOT <m.amiot@otak-arts.com>
	 * @copyright Copyright (c) 2011, Mathieu AMIOT
	 * @version 1.0
	 */
    namespace MuPHP\CookieFav;
	require_once dirname(__FILE__) . '/../../cfg/define.php';

	/**
	 * @package MuPHP
	 * @subpackage CookieFav
	 * Allows to manage per-user favorites inside a serialized cookie
	 */
	class favoritesMan
	{
		static private $data;
		static private $uid;

		/**
		 * Updates the favorites in both ways (saving/loading)
		 * @static
		 * @return void
		 */
		static private function write()
		{
            $serializedData = serialize(self::$data);
            $_COOKIE['FAVORITES'] = $serializedData;
            setcookie('FAVORITES', $serializedData, time() + 31536000, SITE_PATH, null, false, false);
		}

        /**
         * reads the cookie to refresh internal data
         * @static
         * @return void
         */
        static private function read()
        {
            if (isset($_COOKIE['FAVORITES']))
                self::$data = unserialize(stripslashes($_COOKIE['FAVORITES']));
            else
                self::$data = array();

	        @session_start();
	        if (\MuPHP\Accounts\userMan::loggedIn())
	            self::$uid = $_SESSION['USER_DATA']->getUserId();
	        else
		        self::$uid = false;
        }

		/**
		 * Updates and gets the favorites
		 * @static
		 * @return array
		 */
		static public function getFavList()
		{
            self::read();
			if (self::$uid && isset(self::$data[self::$uid]))
                return self::$data[self::$uid];
			else
				return array();
		}

		/**
		 * Adds something to favorites
		 * @static
		 * @param mixed $data
		 * @param int|string|bool $id
		 * @return void
		 */
		static public function addFav($data, $id = false)
		{
			self::read();
			if (!self::$uid) return;
			if (!isset(self::$data[self::$uid]) || array_search($data, self::$data[self::$uid]) === false)
			{
				if ($id === false)
                    self::$data[self::$uid][] = $data;
                else
                    self::$data[self::$uid][$id] = $data;
			}
			self::write();
		}

		/**
		 * Deletes something from favorites, if found, returns true, false otherwise.
		 * @static
		 * @param mixed $data
		 * @param int|string|bool $id
		 * @return bool
		 */
		static public function removeFav($data, $id = false)
		{
            self::read();
			if (!self::$uid) return false;
			$tmp = false;
            if (isset(self::$data[self::$uid]))
            {
                if ($data && ($tmp = array_search($data, self::$data[self::$uid])) !== false)
                    unset(self::$data[self::$uid][$tmp]);
                else if ($id !== false)
                {
                    unset(self::$data[self::$uid][$id]);
                    $tmp = true;
                }
            }

			self::write();
			return $tmp !== false;
		}

        /**
         * Checks if something is faved
         * @static
         * @param $data
         * @param bool $id
         * @return bool
         */
		static public function isFaved($data, $id = false)
		{
            self::read();
			if (!self::$uid || !isset(self::$data[self::$uid])) return false;
            if ($id !== false)
                return isset(self::$data[self::$uid][$id]);
            return array_search($data, self::$data[self::$uid]) !== false;
		}
	}
