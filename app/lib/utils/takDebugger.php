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
     * @subpackage Debugger
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2012, Mathieu AMIOT
     * @version    0.1
     * @changelog
     *      0.1a : in progress
     */
    namespace TakPHPLib\Debug;

    class VariableNotDeclaredException extends \Exception
    {
        public function __construct()
        {
            $this->message = 'The given variable has not been declared in the debugger!';
        }
    }

    class TakDebugger
    {
        const
            TYPE_BOOLEAN = 1,
            TYPE_INT = 2,
            TYPE_STRING = 4,
            TYPE_DOUBLE = 8,
            TYPE_OBJECT = 16,
            TYPE_RESOURCE = 32;

        private static
            $_vars;

        /**
         * Declares a variable in the debugger to be analyzed
         * @static
         * @param $var
         * @param $name
         * @param $type
         */
        static public function declareVar(&$var, $name, $type)
        {
            self::$_vars[$name] = &$var; // lock zval ref
            switch ($type) // Init
            {
                case self::TYPE_BOOLEAN:
                    self::$_vars[$name] = false;
                break;

                case self::TYPE_INT:
                    self::$_vars[$name] = 0;
                break;

                case self::TYPE_STRING:
                    self::$_vars[$name] = '';
                break;

                case self::TYPE_DOUBLE:
                    self::$_vars[$name] = 0.0;
                break;

                case self::TYPE_OBJECT:
                case self::TYPE_RESOURCE:
                default:
                    self::$_vars[$name] = null;
                break;
            }
        }

        /**
         * Dumps a variable declared in current context
         * @static
         * @param $name
         * @throws VariableNotDeclaredException
         */
        static public function dumpDeclaredVar($name)
        {
            if (isset(self::$_vars[$name]))
                self::dumpVar(self::$_vars[$name]);
            else
                throw new VariableNotDeclaredException();
        }


        /**
         *
         * @static
         */
        static public function dumpGetPost()
        {
            if (isset($_POST) && !empty($_POST))
            {
                if (!PHP_SAPI === 'cli') echo '<span style="color: red;">Dump <strong>$_POST</strong> : </span><br />';
                self::dumpVar($_POST);
            }
            if (isset($_GET) && !empty($_GET))
            {
                if (!PHP_SAPI === 'cli') echo '<span style="color: green;">Dump <strong>$_GET</strong> : </span><br />';
                self::dumpVar($_GET);
            }
        }

        /**
         * @static
         * @param mixed $var
         */
        static public function dumpVar(&$var)
        {
            if (!PHP_SAPI === 'cli') echo '<pre>';
            ini_set('xdebug.var_display_max_depth', -1);
            var_dump($var);
            if (!PHP_SAPI === 'cli') echo '</pre>';
        }
    }