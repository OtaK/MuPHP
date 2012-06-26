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
	 * @version 1.2
     * @changelog
	 *      1.2 : Added Semaphore design pattern
     *      1.1 : Added Observer/Observable patterns
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
        /**
         * @static
         * @abstract
         * @param $className
         * @return mixed
         */
        public static function factory($className);
    }

    /**
     * Interface for Observables
     * @interface
     */
    interface iObservable
    {
        /**
         * @abstract
         * @return void
         */
        public function notifyObservers();

        /**
         * @abstract
         * @param iObserver $obj
         * @return void
         */
        public function addObserver(iObserver &$obj);

        /**
         * @abstract
         * @param iObserver $obj
         * @return void
         */
        public function removeObserver(iObserver &$obj);
    }

    /**
     * Basic implementation of iObservable interface
     * @abstract
     */
    abstract class Observable implements iObservable
    {
        /** @var array */
        private $_observers;
        /**
         * @return void
         */
        public function notifyObservers()
        {
            foreach ($this->_observers as &$curObserver)
            {
                /** @var iObserver $curObserver */
                $curObserver->update($this);
            }
        }

        /**
         * @param iObserver $obj
         * @return void
         */
        public function addObserver(iObserver &$obj)
        {
            if (array_search($obj, $this->_observers, true) !== false)
                return;
            $this->_observers[] = $obj;
        }

        /**
         * @param iObserver $obj
         * @return void
         */
        public function removeObserver(iObserver &$obj)
        {
            if (($index = array_search($obj, $this->_observers, true)) === false)
                return;
            unset($this->_observers[$index]);
            if ($index === ($c = count($this->_observers)))
                return;
            $this->_observers[$index] = $this->_observers[$c - 1];
            unset($this->_observers[$c - 1]);
        }
    }

    /**
     * Interface for Observers
     */
    interface iObserver
    {
        /**
         * @abstract
         * @param iObservable $obj
         * @return void
         */
        public function update(iObservable &$obj = null);
    }

    /**
     * Basic implementation of iObserver, takes a callback and executes it
     * @abstract
     */
    abstract class Observer implements iObserver
    {
        /** @var \Closure callback */
        private $_callback;

        /**
         * Ctor
         * @param Closure $callback
         */
        public function __construct(\Closure &$callback) { $this->_callback = $callback; }

        /**
         * @param iObservable $obj
         * @return void
         */
        public function update(iObservable &$obj = null) { $this->${'_callback'}($obj); }
    }

    /**
     * Class to manage semaphores
     */
    class Semaphore
    {
        const
                SEMAPHORE_LOCATION = '/tmp/eperflex_semaphore';

        static private
                $_lockData,
                $_loaded = false;

        /**
         * Gets the data from the file
         * @static
         */
        static private function _getLockData()
        {
            if (file_exists(self::SEMAPHORE_LOCATION))
                self::$_lockData = json_decode(file_get_contents(self::SEMAPHORE_LOCATION), true);
            else
                self::$_lockData = array();
            self::$_loaded = true;
        }

        /**
         * Puts current data into file
         * @static
         * @return void
         */
        static private function _putLockData()
        {
            if (!self::$_loaded) return;
            file_put_contents(self::SEMAPHORE_LOCATION, json_encode(self::$_lockData), LOCK_EX);
        }

        /**
         * Locks a key into the semaphore
         * @static
         * @param $key
         */
        static public function lock($key)
        {
            if (!self::$_loaded)
                self::_getLockData();
            self::$_lockData[$key] = true;
            self::_putLockData();
        }

        /**
         * Unlocks a key from the semaphore
         * @static
         * @param $key
         */
        static public function unlock($key)
        {
            if (!self::$_loaded)
                self::_getLockData();
            self::$_lockData[$key] = false;
            self::_putLockData();
        }

        /**
         * Checks if a given key is currently locked in the semaphore
         * @static
         * @param int|string $key
         * @return bool
         */
        static public function isLocked($key)
        {
            if (!self::$_loaded)
                self::_getLockData();
            return (isset(self::$_lockData[$key]) && self::$_lockData[$key]);
        }
    }
