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

    namespace MuPHP\Cache;
    require_once __DIR__ . '/../abstraction/designPatterns.php';

    class MemcacheNotFoundException extends \Exception
    {
        public function __construct($message = "", $code = 0, \Exception $previous = null)
        {
            parent::__construct('Memcache library could not be found, exiting...', $code, $previous);
        }
    }

    class RedisNotFoundException extends \Exception
    {
        public function __construct($message = "", $code = 0, \Exception $previous = null)
        {
            parent::__construct('Redis extension could not be found, exiting...', $code, $previous);
        }
    }

    class CacheEngineNotSet extends \Exception
    {
        public function __construct($message = "", $code = 0, \Exception $previous = null)
        {
            parent::__construct('You didn\'t choose a cache engine yet!', $code, $previous);
        }
    }

    /**
     * Caching engine with internal json serialization
     * @package    MuPHP
     * @subpackage Cache
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2013, Mathieu AMIOT
     * @version    1.0
     * @changelog
     *      1.0 : Initial release
     */
    class CacheProvider extends \MuPHP\DesignPatterns\Singleton
    {
        const
                CACHE_NONE = 0x0,
                CACHE_MEMCACHE = 0x1,
                CACHE_REDIS = 0x2;

        /**
         * @var int
         */
        private $_currentProvider;

        /**
         * @var \Redis|\Memcache
         */
        private $_provider;

        /**
         * Singleton stuff
         * @param int $provider
         * @return CacheProvider
         */
        public static function get_instance($provider = CACHE_DEFAULT_ENGINE)
        {
            if (!isset(self::$_instance))
                self::$_instance = new CacheProvider($provider);
            return self::$_instance;
        }

        /**
         * Ctor
         * @param int $provider
         */
        protected function __construct($provider = self::CACHE_NONE)
        {
            $this->setProvider($provider);
        }

        /**
         * Dtor
         */
        public function __destruct()
        {
            if (self::CACHE_REDIS == $this->_currentProvider)
                $this->_provider->save(); // Save redis to disk before saying bye !

            $this->_provider->close();
        }

        /**
         * @param int $provider constant
         * @throws MemcacheNotFoundException
         * @throws RedisNotFoundException
         */
        public function setProvider($provider)
        {
            if (self::CACHE_MEMCACHE === $provider) // Memcache based cache engine
            {
                if (!class_exists('\\Memcache'))
                    throw new MemcacheNotFoundException();

                $this->_currentProvider = self::CACHE_MEMCACHE;
                $this->_provider = new \Memcache();
                $this->_provider->connect(CACHE_HOST, CACHE_PORT);
            }
            else if (self::CACHE_REDIS === $provider) // Redis-based cache engine
            {
                if (!class_exists('\\Redis'))
                    throw new RedisNotFoundException();

                $this->_currentProvider = self::CACHE_REDIS;
                $this->_provider = new \Redis();
                $this->_provider->connect(CACHE_HOST, CACHE_PORT);
                // JSON serialization is better for cross platform stuff
                $this->_provider->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
            }
            else
            {
                $this->_currentProvider = self::CACHE_NONE;
                $this->_provider = null;
            }
        }

        /**
         * Set a key-value pair (or several) in caching provider
         * @param string|array $key
         * @param string|int|array $value
         * @param float|int $timeout
         * @throws CacheEngineNotSet
         * @return bool
         */
        public function set($key, $value, $timeout = 0)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            if (is_array($key) && is_array($value)) // If we have arrays of keys & values, handle it like a sir
                return $this->multiSet($key, $value, $timeout);

            $cacheKey = $this->_prefixKey($key);
            $cacheValue = is_array($value) ? json_encode($value) : $value;

            if ($this->_currentProvider === self::CACHE_REDIS)
                return $timeout ? $this->_provider->setex($cacheKey, (int)$timeout, $cacheValue) : $this->_provider->set($cacheKey, $cacheValue);
            else if ($this->_currentProvider === self::CACHE_MEMCACHE)
                return $this->_provider->set($cacheKey, $cacheValue, 0, $timeout);

            return false;
        }

        /**
         * Set multiple key-value pairs in caching provider
         * @param array $keys
         * @param array $values
         * @param float|int $timeout
         * @return bool
         * @throws CacheEngineNotSet
         */
        public function multiSet(array $keys, array $values, $timeout = 0)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            $l  = count($keys);
            if ($l !== count($values)) return false;

            // Prefix all keys
            $mKeys = array();
            foreach ($keys as $k)
                $mKeys[] = $this->_prefixKey($k);

            // Encode values if necessary
            foreach ($values as &$val)
                $val = is_array($val) ? json_encode($val) : $val;

            if ($this->_currentProvider === self::CACHE_REDIS)
            {
                return $this->_provider->msetnx(array_combine($mKeys, $values));
            }
            else if ($this->_currentProvider === self::CACHE_MEMCACHE)
            {
                $result = true;
                for ($i = 0; $i < $l; ++$i)
                {
                    if (!$this->_provider->set($mKeys[$i], $values[$i], 0, $timeout))
                        $result = false;
                }
                return $result;
            }

            return false;
        }

        /**
         * Get a value (or several) from its key
         * @param string|array $key
         * @return array|string|bool
         * @throws CacheEngineNotSet
         */
        public function get($key)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            if (is_array($key)) // If we have an array of keys, handle it like a sir
                return $this->multiGet($key);

            $cacheKey = $this->_prefixKey($key);
            $result = $this->_provider->get($cacheKey);

            $tmp = json_decode($result, true);
            if (json_last_error() === JSON_ERROR_NONE)
                return $tmp;

            return $result;
        }

        /**
         * Get several values from a key array
         * @param array $keys
         * @return array
         * @throws CacheEngineNotSet
         */
        public function multiGet(array $keys)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            $result = array();
            $mKeys = array();
            foreach ($keys as $k)
                $mKeys[] = $this->_prefixKey($k);

            if ($this->_currentProvider === self::CACHE_MEMCACHE)
            {
                $tmp = $this->_provider->get($mKeys); // Memcache supports it out of box
                $magicKeys = array_fill_keys($keys, false);
                $result = array_merge($magicKeys, $tmp);
            }
            else if ($this->_currentProvider === self::CACHE_REDIS)
                $result = array_combine($keys, $this->_provider->mget($mKeys));

            return $result;
        }

        /**
         * Increment provided key by value
         * @param     $key
         * @param int $value
         * @return bool|void
         * @throws CacheEngineNotSet
         */
        public function increment($key, $value = 1)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            $val = false;
            $prefixKey = $this->_prefixKey($key);
            if ($this->_currentProvider === self::CACHE_MEMCACHE)
                $val = $this->_provider->increment($prefixKey, $value);
            else if ($this->_currentProvider === self::CACHE_REDIS)
                $val = $this->_provider->incrBy($prefixKey, $value);

            return $val;
        }

        /**
         * Decrement provided key by value
         * @param     $key
         * @param int $value
         * @return bool|void
         * @throws CacheEngineNotSet
         */
        public function decrement($key, $value = 1)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            $val = false;
            $prefixKey = $this->_prefixKey($key);
            if ($this->_currentProvider === self::CACHE_MEMCACHE)
                $val = $this->_provider->decrement($prefixKey, $value);
            else if ($this->_currentProvider === self::CACHE_REDIS)
                $val = $this->_provider->decrBy($prefixKey, $value);

            return $val;
        }

        /**
         * Private utility function to prefix provided key with defined constants
         * @param string $key
         * @return string
         */
        private function _prefixKey($key)
        {
            return strpos($key, CACHE_KEYPREFIX) === false ? CACHE_KEYPREFIX . ':' . $key : $key;
        }

        /**
         * @param int $provider
         */
        public static function EnableSessionCaching($provider)
        {
            switch ($provider)
            {
                case self::CACHE_REDIS:
                    ini_set('session.save_handler', 'redis');
                    ini_set('session.save_path', 'tcp://'.CACHE_HOST.':'.CACHE_PORT.'/');
                    break;
                case self::CACHE_MEMCACHE:
                    ini_set('session.save_handler', 'memcache');
                    ini_set('session.save_path', 'tcp://'.CACHE_HOST.':'.CACHE_PORT.'/');
                    break;
                case self::CACHE_NONE:
                default:
                    ini_set('session.save_handler', 'files');
                    ini_set('session.save_path', '/tmp/php/sessions');

            }
        }
    }
