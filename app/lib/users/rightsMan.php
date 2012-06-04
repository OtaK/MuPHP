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
	 * @subpackage Auth
	 * @author Mathieu AMIOT <m.amiot@otak-arts.com>
	 * @copyright Copyright (c) 2012, Mathieu AMIOT
	 * @version 1.1
	 * @changelog
     *      1.1 : Introduction of namespace use
	 *      1.0 : initial release
	 */
    namespace TakPHPLib\Auth;

    /**
     * @package    TakPHPLib
     * @subpackage Auth
     *             Manages the rights of users across pages of the website specified in the $modules arg of the ctor
     */
    class rightsMan
    {
        static private
            $_siteModules;

        /**
         * Ctor
         * @param array $modules
         */
        public function __construct(array &$modules)
        {
            if (isset(self::$_siteModules)) return;
            self::$_siteModules = &$modules;
        }

        /**
         * Gets the site modules as a kinda-singleton way
         * @static
         * @return array
         */
        static public function getModules() { return self::$_siteModules; }

        /**
         * Checks if the current user has the right to access the page
         * @static
         * @param string $pageName
         * @return bool
         */
        public function isAuthorized($pageName)
        {
            if (!isset(self::$_siteModules[$pageName]))
                return false;

            if (self::$_siteModules[$pageName]['registeredOnly'])
            {
                if (\TakPHPLib\Accounts\userMan::loggedIn())
                {
                    if (self::$_siteModules[$pageName]['adminOnly'])
                        return (\TakPHPLib\Accounts\userMan::currentUser()->isAdmin());

                    return true;
                }
                return false;
            }
            return true;
        }
    }
