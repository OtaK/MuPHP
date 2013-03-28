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
     * @package    MuPHP
     * @subpackage DB
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2013, Mathieu AMIOT
     * @version    1.7
     * @changelog
     *      1.7 : Added composite arguments (%v) to query for array arguments. (nice for IN() queries)
     *      1.6 : Added cached query support
     *      1.5.1 : Added db name property
     *      1.5 : Added iteration modes and XSS protection mode to dbResult
     *      1.4.1 : Added design pattern usage hint to dbMan
     *      1.4 : Added Iterator support to dbResult, so it can be browsed with a foreach loop
     *      1.3 : Multiple connection support
     *      1.2 : Introduction of namespace use
     *      1.1 : Added magic method to get MySQLi_Result properties + doc for IDE hinting
     *      1.0 : security and backwards compatibility fixes with PHP 5.2.x
     *      0.9b : initial release
     */
    namespace MuPHP\DB;
    require_once __DIR__ . '/../abstraction/designPatterns.php';
    require_once __DIR__ . '/../cache/CacheProvider.php';

    /**
     * dbMan is an overlay (and a singleton) to MySQLi and allows queries to be automatically escaped against SQL injections
     * It depends on the dbResult class further below in this subpackage
     */
    class dbMan extends \mysqli implements \MuPHP\DesignPatterns\iSingleton
    {
        /** @var dbMan $_instance           Singleton instance */
        private static $_instance;
        /** @var array $_connectionInfo     Saved connection info for connection updates */
        private static $_connectionInfo;

        /** @var string */
        public $db;

        /**
         * Ctor
         * @param string $host
         * @param string $user
         * @param string $pass
         * @param string $dbName
         */
        protected function __construct($host = DBHOST, $user = DBUSER, $pass = DBPWD, $dbName = DBBASE)
        {
            parent::__construct($host, $user, $pass, $dbName);
            $this->db = $dbName;
            parent::set_charset('utf8');
        }

        /**
         * Singleton call
         * @static
         * @param string $host
         * @param string $user
         * @param string $pass
         * @param string $dbName
         * @param bool   $isNewInstance
         * @return dbMan
         */
        static public function get_instance($host = DBHOST, $user = DBUSER, $pass = DBPWD, $dbName = DBBASE, $isNewInstance = false)
        {
            if ($isNewInstance)
                return new dbMan($host, $user, $pass, $dbName);

            if (!isset(self::$_instance))
                self::$_instance = new dbMan($host, $user, $pass, $dbName);

            self::$_instance->renewConnection($host, $user, $pass, $dbName);
            return self::$_instance;
        }

        /**
         * Updates connection if necessary with new parameters
         * @param string $host
         * @param string $user
         * @param string $pass
         * @param string $dbName
         * @return void
         */
        private function renewConnection($host, $user, $pass, $dbName)
        {
            $update = false;
            if (isset(self::$_connectionInfo))
            {
                if (self::$_connectionInfo['host'] !== $host || self::$_connectionInfo['user'] !== $user)
                {
                    $this->real_connect($host, $user, $pass, $dbName);
                    $update = true;
                }
                else if (self::$_connectionInfo['dbName'] !== $dbName)
                {
                    $update = true;
                    try { $this->select_db($dbName); }
                    catch (\Exception $e) // Revert in case of problem
                    {
                        $update = false;
                        $this->select_db(self::$_connectionInfo['dbname']);
                    }
                }
            }

            if (!$update) return;
            self::$_connectionInfo = array(
                'host'   => $host,
                'user'   => $user,
                'dbName' => $dbName
            );
        }

        /**
         * Overloaded select_db function to throw exceptions and also to support chained object calls
         * @param $dbname
         * @return dbMan
         * @throws \Exception
         */
        public function select_db($dbname)
        {
            if (!parent::select_db($dbname))
                throw new \Exception('The database could not be selected ! Exiting...');
            $this->db = $dbname;
            return $this;
        }

        /**
         * Executes a safe query and returns the result of it
         * @param string $query
         * @param array  $params
         * @return boolean|dbResult
         */
        public function query($query, array $params = array())
        {
            $result = parent::query($this->safeQuery($query, $params));
            return is_bool($result) ? $result : new dbResult($result);
        }

        /**
         * Executes a safe query using the current caching mechanism (cached if select only)
         * @param $query
         * @param array $params
         * @return array|bool|dbResult|string
         */
        public function cachedQuery($query, array $params = array())
        {
            if (stripos($query, 'select') === false)
                return $this->query($query, $params);

            $safeQ = $this->safeQuery($query, $params);
            $cacheKey = CACHE_SQLCACHE_PREFIX.md5($safeQ);
            $cacheRes = \MuPHP\Cache\CacheProvider::get_instance()->get($cacheKey);
            if ($cacheRes !== false)
                return $cacheRes;

            $queryRes = parent::query($safeQ);
            if (is_bool($queryRes))
                return $queryRes;

            $queryRes = new dbResult($queryRes);
            /** @var $queryRes dbResult */
            $cacheRes = $queryRes->fetch_all(MYSQLI_ASSOC);
            \MuPHP\Cache\CacheProvider::get_instance()->set($cacheKey, $cacheRes);
            return $cacheRes;
        }

        /**
         * Generates an escaped query secure against SQL Injections
         * @param       $query
         * @param array $params
         * @return string
         */
        public function safeQuery($query, array $params = array())
        {
            if (!count($params))
                return $query;

	    if (stripos($query, '%v') !== false)
                self::_filterCompositeArgs($query, $params, $this);

            array_walk($params, '\MuPHP\DB\dbMan::escapeCallback', $this);
            return vsprintf($query, $params);
        }

        /**
         * @static
         * @param string $query
         * @param array  $params
         * @param dbMan  $helper
         * @return void
         */
        private static function _filterCompositeArgs(&$query, array &$params = array(), dbMan &$helper)
        {
            $paramIndex = 0;
            $callback = function($matches) use (&$paramIndex, &$params, &$helper)
            {
                // if vd or vs, replace dat shit
                $result = $matches[0];
                if (stripos($result, '%v') !== false && is_array($params[$paramIndex]))
                {
                    $data = $params[$paramIndex];
                    foreach ($data as &$d)
                        $d = $helper->real_escape_string($d);

                    unset($params[$paramIndex]);
                    $result = "'" . implode("','", $data) . "'";
                }

                // If other placeholder, do nothing
                ++$paramIndex;
                return $result;
            };

            $params = array_values($params);
            $query = preg_replace_callback('/%[b|c|d|e|E|u|f|F|g|G|o|s|x|X|v|V]/', $callback, $query);
        }

        /**
         * Helper function that walks the array of args, escaping all the params
         * @callback
         * @static
         * @param mixed $value
         * @param mixed $key    useless, just for the callback params
         * @param dbMan $helper self used for the escape string call
         * @return void
         */
        private static function escapeCallback(&$value, $key, dbMan $helper)
        {
            $value = $helper->real_escape_string($value);
        }

        /**
         * Executes a single-row safe query and returns the array containing that row
         * @param string $query
         * @param array  $params
         * @param int    $mode
         * @param bool   $xss
         * @return array
         */
        public function singleResQuery($query, array $params = array(), $mode = MYSQLI_ASSOC, $xss = false)
        {
            $qRes = $this->query($query, $params);
            if (!$qRes || !$qRes->num_rows) return false;
            return $qRes->fetch_array($mode, $xss);
        }

        /**
         * Executes a multi-row safe query and returns an array containing that result set of all rows
         * @param string $query
         * @param array  $params
         * @param int    $mode
         * @param bool   $xss
         * @return array
         */
        public function multiResQuery($query, array $params = array(), $mode = MYSQLI_ASSOC, $xss = false)
        {
            $qRes = $this->query($query, $params);
            if (!$qRes) return false;
            return $qRes->fetch_all($mode, $xss);
        }

        /**
         * Executes a multi-row safe query that will have one result column.
         * @param       $query
         * @param array $params
         * @param bool  $xss
         * @return array|bool
         */
        public function singleColumnQuery($query, array $params = array(), $xss = false)
        {
            $qRes = $this->query($query, $params);
            if (!$qRes) return false;
            for ($res = array(); $tmp = $qRes->fetch_row($xss); $res[] = $tmp[0]) ;
            return $res;
        }
    }

    /**
     * @package    MuPHP
     * @subpackage DB
     *             dbResult is an overlay to MySQLi_Result
     *             It allows to escape results produced by dbMan::query() against XSS attacks
     * @see        dbMan::query()
     *
     * Properties:
     * @property-read int $num_rows
     * @property-read int $field_count
     * @property-read int $current_field
     * @property-read array $lengths
     */
    class dbResult implements \Iterator
    {
        const
            ITERATE_ASSOC = MYSQLI_ASSOC,
            ITERATE_NUM   = MYSQLI_NUM,
            ITERATE_BOTH  = MYSQLI_BOTH;

        private
            /** @var \MySQLi_Result */
            $_innerRes,
            /** @var int */
            $_recordNumber,
            /** @var array|null */
            $_currentRecord,
            /** @var int */
            $_iterateMode,
            /** @var bool */
            $_iterateWithXSS;


        /**
         * Ctor
         * @param \MySQLi_Result $res
         */
        public function __construct(\MySQLi_Result &$res)
        {
            $this->_innerRes       = $res;
            $this->_recordNumber   = -1;
            $this->_currentRecord  = null;
            $this->_iterateMode    = self::ITERATE_ASSOC;
            $this->_iterateWithXSS = false;
        }

        /**
         * Dtor
         */
        public function __destruct()
        {
            $this->_innerRes->free();
            unset($this->_innerRes, $this->_currentRecord);
        }

        /**
         * @see mysqli_result::free()
         */
        public function free()
        {
            $this->_innerRes->free();
            unset($this->_currentRecord);
            $this->_currentRecord = null;
            $this->_recordNumber  = -1;
        }

        /**
         * Magic method to get properties from the inner MySQLi_Result object
         * @param $name
         * @return null
         */
        public function __get($name)
        {
            if (property_exists($this->_innerRes, $name))
                return $this->_innerRes->{$name};
            return null;
        }

        /**
         * Sets iteration mode when used with foreach()
         * Defaults to ITERATE_ASSOC
         * @param int $val
         * @return bool
         */
        public function setIterationMode($val = self::ITERATE_ASSOC)
        {
            if ($val === self::ITERATE_ASSOC || $val === self::ITERATE_NUM || $val === self::ITERATE_BOTH)
            {
                $this->_iterateMode = $val;
                return true;
            }
            return false;
        }

        /**
         * Sets iteration mode with XSS protection enabled or not
         * Defaults to off (false)
         * @param bool $val
         * @return bool
         */
        public function setIterateWithXss($val = false)
        {
            if (is_bool($val))
            {
                $this->_iterateWithXSS = $val;
                return true;
            }
            return false;
        }

        /**
         * @see mysqli_result::data_seek
         * @param int $offset
         * @return bool
         */
        public function data_seek($offset)
        {
            $realRecordNr = $offset ? $offset - 1 : 0;
            if ($res = $this->_innerRes->data_seek($realRecordNr))
                $this->_recordNumber = $realRecordNr;
            return $res;
        }

        /**
         * @return int
         */
        public function key()
        {
            return $this->_recordNumber;
        }

        public function getRecordNumber()
        {
            return $this->_recordNumber;
        }

        /**
         * @return array|null
         */
        public function current()
        {
            return $this->_currentRecord;
        }

        /**
         * Goes to first record
         * @return bool
         */
        public function first()
        {
            return $this->data_seek(0);
        }

        /**
         * @see dbResult::first()
         * @return void
         */
        public function rewind()
        {
            $this->first();
            $this->next();
        }

        /**
         * @see Iterator::valid()
         * @return bool
         */
        public function valid()
        {
            return (bool)$this->_currentRecord;
        }

        /**
         * Goes to last result
         * @return bool
         */
        public function last()
        {
            if ($this->_innerRes->data_seek($this->_innerRes->num_rows - 2))
            {
                $this->_recordNumber = $this->_innerRes->num_rows - 2;
                $this->fetch_assoc();
                return true;
            }
            return false;
        }

        /**
         * Fetches next line when used with foreach()
         * @return void
         */
        public function next()
        {
            $this->fetch_array($this->_iterateMode, $this->_iterateWithXSS);
        }

        /**
         * Fetches a line from the query result
         * @param int  $resulttype result type among the constants MYSQLI_BOTH, MYSQLI_ASSOC, MYSQLI_NUM
         * @param bool $xss        enable or not XSS protection, defaults to false
         * @return array|boolean
         */
        public function fetch_array($resulttype = MYSQLI_BOTH, $xss = false)
        {
            $res = $this->_innerRes->fetch_array($resulttype);
            if ($res)
            {
                ++$this->_recordNumber;
                if ($xss) array_walk($res, '\MuPHP\DB\dbResult::xssProtectCallback');
            }
            return $this->_currentRecord = &$res;
        }

        /**
         * Calls dbResult::fetch_array() with the MYSQLI_ASSOC mode
         * @see dbResult::fetch_array()
         * @param bool $xss
         * @return array|bool
         */
        public function fetch_assoc($xss = false)
        {
            return $this->fetch_array(MYSQLI_ASSOC, $xss);
        }

        /**
         * Calls dbResult::fetch_array() with the MYSQLI_NUM mode
         * @see dbResult::fetch_array()
         * @param bool $xss
         * @return array|bool
         */
        public function fetch_row($xss = false)
        {
            return $this->fetch_array(MYSQLI_NUM, $xss);
        }

        /**
         * Fetches metadata of next field with XSS protection
         * @see MySQLi_Result::fetch_field()
         * @return array|object
         */
        public function fetch_field()
        {
            return $this->_innerRes->fetch_field();
        }

        /**
         * Fetches metadata of all the fields with XSS protection
         * @param bool $xss
         * @return array
         */
        public function fetch_fields($xss = false)
        {
            $res = $this->_innerRes->fetch_fields();
            if ($xss && $res) array_walk($res, '\MuPHP\DB\dbResult::xssProtectCallback');
            return $res;
        }

        /**
         * Fetches metadata of a particular field
         * @param $fieldnr
         * @return array|object
         */
        public function fetch_field_direct($fieldnr)
        {
            return $this->_innerRes->fetch_field_direct($fieldnr);
        }

        /**
         * Returns an array of the whole resultset as an array with the specified mode
         * Also has a compatibility layer with PHP < 5.3 because mysqli::fetch_all appeared in this version
         * @param int  $resulttype defaults now to MYSQLI_ASSOC instead of MYSQLI_NUM, better
         * @param bool $xss
         * @return array
         */
        public function fetch_all($resulttype = MYSQLI_ASSOC, $xss = false)
        {
            if (method_exists($this->_innerRes, 'fetch_all')) // Compatibility layer with PHP < 5.3
                $res = $this->_innerRes->fetch_all($resulttype);
            else
                for ($res = array(); $tmp = $this->_innerRes->fetch_array($resulttype); $res[] = $tmp);

            if ($xss && $res) array_walk_recursive($res, '\MuPHP\DB\dbResult::xssProtectCallback');
            return $res;
        }

        /**
         * Callback to XSS protect an array member
         * @callback
         * @static
         * @param string $val
         * @param string $key useless, just for the callback params scheme
         * @return void
         */
        static public function xssProtectCallback(&$val, $key)
        {
            $val = htmlspecialchars($val);
        }
    }
