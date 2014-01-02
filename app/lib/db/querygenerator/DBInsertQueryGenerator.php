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
     * @subpackage QueryGenerator
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2013, Mathieu AMIOT
     * @version    0.1
     * @changelog
     *      0.1 : dev in progress
     */

    namespace MuPHP\DB\QueryGenerator;

    use MuPHP\DB\DBMan;

    class DBInsertQueryGenerator extends DBUpdateQueryGenerator
    {
        protected $_values;
        protected $_onDuplicateKey;

        /**
         * @param       $table
         * @param DBMan $db
         */
        public function __construct($table, DBMan $db = null)
        {
            parent::__construct($table, $db);
            $this->_values         = array();
            $this->_onDuplicateKey = array();
        }

        public function set($field, $value, $quoted = true)
        {
            if ($this->_set === null)
            {
                $this->_set = array();
                $this->_values = null;
            }

            return parent::set($field, $value, $quoted);
        }

        public function values(array $values)
        {
            if (count($values) === 0)
                return $this;

            $this->_set    = null;
            $this->_values = $values;

            return $this;
        }

        public function onDuplicateKey($field, $value, $quoted = true)
        {
            if ($quoted)
                $value = "'{$this->_db->escape_string($value)}'";

            $this->_onDuplicateKey[$field] = $value;

            return $this;
        }

        /**
         * @return string
         */
        public function getQuery()
        {
            $q = "INSERT INTO {$this->_table} ";

            if ($this->_set === null)
            {
                $q .= " VALUES ";
                foreach ($this->_values as $data)
                {
                    $values = array_values($data);
                    $q .= "({implode(',', $values)}),";
                }

                $q = substr($q, 0, -1);
            }
            else
            {
                $q .= " SET ";
                foreach ($this->_set as $field => $val)
                    $q .= "$field = $val, ";
                $q = substr($q, 0, -2);
            }

            if (count($this->_onDuplicateKey) > 0)
            {
                $q .= " ON DUPLICATE KEY ";
                foreach ($this->_onDuplicateKey as $field => $val)
                    $q .= "$field = $val, ";
                $q = substr($q, 0, -2);
            }

            return $q;
        }
    }
