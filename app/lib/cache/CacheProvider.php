<?php
    namespace MuPHP\Cache;

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
         * @return bool
         * @throws CacheEngineNotSet
         */
        public function set($key, $value)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            if (is_array($key) && is_array($value)) // If we have arrays of keys & values, handle it like a sir
                return $this->multiSet($key, $value);

            $cacheValue = is_array($value) ? json_encode($value) : $value;

            return $this->_provider->set($this->_prefixKey($key), $cacheValue);
        }

        /**
         * Set multiple key-value pairs in caching provider
         * @param array $keys
         * @param array $values
         * @return bool
         * @throws CacheEngineNotSet
         */
        public function multiSet(array $keys, array $values)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            $l  = count($keys);
            if ($l !== count($values)) return false;
            $result = true;

            for ($i = 0; $i < $l; ++$i)
            {
                $cacheValue = is_array($values[$i]) ? json_encode($values[$i]) : $values[$i];
                if (!$this->_provider->set($this->_prefixKey($keys[$i]), $cacheValue))
                    $result = false;
            }

            return $result;
        }

        /**
         * Get a value (or several) from its key
         * @param string|array $key
         * @return array|string
         * @throws CacheEngineNotSet
         */
        public function get($key)
        {
            if ($this->_currentProvider === self::CACHE_NONE)
                throw new CacheEngineNotSet();

            if (is_array($key)) // If we have an array of keys, handle it like a sir
                return $this->multiGet($key);

            $cacheKey = $this->_prefixKey($key);
            return $this->_provider->get($cacheKey);
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
         * Private utility function to prefix provided key with defined constants
         * @param string $key
         * @return string
         */
        private function _prefixKey($key)
        {
            return strpos($key, CACHE_KEYPREFIX) === false ? CACHE_KEYPREFIX . ':' . $key : $key;
        }

    }
