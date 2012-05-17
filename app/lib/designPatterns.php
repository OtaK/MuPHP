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
	 * @subpackage DesignPatterns
	 * @author Mathieu AMIOT <m.amiot@otak-arts.com>
	 * @copyright Copyright (c) 2011, Mathieu AMIOT
	 * @version 1.0
     * @changelog
     *      1.0 : initial release
	 */
    namespace TakPHPLib\DesignPatterns;


    /**
     * Base interface for singletons
     * Only use if you can't use the Singleton abstract class because of multiple inheritance
     * @interface
     */
    interface iSingleton
    {
        public static function get_instance();
    }

    /**
     * Class provinding the base structure of a singleton
     * @abstract
     */
    abstract class Singleton implements iSingleton
    {
        static protected $_instance;

        protected function __construct() {}

        public static function get_instance()
        {
            if (!isset(self::$_instance))
            {
                $tmp = new \ReflectionClass(__CLASS__);
                self::$_instance = $tmp->newInstanceArgs(func_get_args());
            }
            return self::$_instance;
        }
    }

    /**
     * Base interface for multitons
     * Only use if you can't use the Multiton abstract class because of multiple inheritance
     * @interface
     */
    interface iMultiton
    {
        public static function get_instance($instanceName, array $args);
    }

    /**
     * Class provinding the base structure of a singleton
     * @abstract
     */
    abstract class Multiton implements iMultiton
    {
        static protected $_instances;

        protected function __construct() {}

        public static function get_instance($instanceName, array $args)
        {
            if (!isset(self::$_instances[$instanceName]))
            {
                $c = new \ReflectionClass(__CLASS__);
                self::$_instances[$instanceName] = $c->newInstanceArgs($args);
            }
            return self::$_instances[$instanceName];
        }
    }

    /**
     * Interface for factories
     * @interface
     */
    interface Factory
    {
        public static function factory($className);
    }