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
	 * @subpackage DataValidation
	 * @author Mathieu AMIOT <m.amiot@otak-arts.com>
	 * @copyright Copyright (c) 2013, Mathieu AMIOT
	 * @version 1.0
	 * @changelog
     *      1.0 : initial release
	 */
    class dataValidator
    {
        const
            VALIDATE_EMAIL = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',
            VALIDATE_PHONE = '/^0[1-9](([0-9]{2}[.]){4}|([0-9]{2}[-]{4})|([0-9]{2}[ ]{4}))$/',
            VALIDATE_WEBSITE = '';

        /**
         * Validates data from a set of constant regexps
         * @static
         * @param $data
         * @param $validationType
         * @return bool
         */
        public static function validate($data, $validationType)
        {
            if ($validationType !== self::VALIDATE_EMAIL 
            || $validationType !== self::VALIDATE_PHONE) return false; // Check all the consts
            return (bool)preg_match($validationType, $data); // check and return
        }
    }
