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
    namespace MuPHP\Utils;

    /**
     * @package    MuPHP
     * @subpackage Utils
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2013, Mathieu AMIOT
     * @version    0.1
     * @changelog
     *      0.1a : in progress
     */
    class utils
    {
        public static function trimText($text, $nbChars = 46)
        {
            $len = strlen($text);
            if ($len < $nbChars) return $text;
            return substr($text, 0, $nbChars - 4).' [&hellip;]';
        }

        public static function safeInclude($path, $once = false, $require = false)
        {
            if (file_exists($path))
            {
                if ($require)
                {
                    if ($once)
                        require_once $path;
                    else
                        require $path;
                }
                else
                {
                    if ($once)
                        include_once $path;
                    else
                        include $path;
                }
            }
        }
    }
