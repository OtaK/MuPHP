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


    class DBSelectQueryGenerator extends DBQueryGenerator
    {

        private $_fields;
        private $_joins;
        private $_conditions;
        private $_orders;
        private $_limit;

        /**
         * @param       $table
         * @param array $fields
         * @param DBMan $db
         */
        public function __construct($table, array $fields = array('*'), DBMan $db = null)
        {
            parent::__construct($table, $db);
            $this->_fields     = $fields;
            $this->_conditions = array();
            $this->_joins      = array();
            $this->_orders     = array();
        }

        public function select($field)
        {
            if (count($this->_fields) === 1 && $this->_fields[0] === '*')
                $this->_fields = array();

            if (!in_array($field, $this->_fields))
                $this->_fields[] = $field;

            return $this;
        }

        /**
         * @param      $field
         * @param      $op
         * @param      $value
         * @param bool $quoted
         * @return $this
         */
        public function where($field, $op, $value, $quoted = true)
        {
            $value = $this->_db->escape_string($value);
            if ($quoted)
                $value = "'$value'";

            $w = "$field $op $value";
            if (!in_array($w, $this->_conditions))
                $this->_conditions[] = $w;

            return $this;
        }

        /**
         * @param $table
         * @param $using
         * @return $this
         */
        public function join($table, $using)
        {
            $j = "$table USING($using)";
            if ($table !== $this->_table && !in_array($j, $this->_joins))
                $this->_joins[] = $j;

            return $this;
        }

        /**
         * @param      $field
         * @param bool $desc
         * @return $this
         */
        public function orderBy($field, $desc = false)
        {
            $this->_orders[] = $field . ($desc ? ' DESC' : ' ASC');

            return $this;
        }

        public function limit($offset, $count = null)
        {
            if ($count !== null)
            {
                $this->_limit = array(
                    'offset' => $offset,
                    'count'  => $count
                );
            }
            else
                $this->_limit = $offset;

            return $this;
        }

        /**
         * @return string
         */
        public function getQuery()
        {
            $q = "SELECT {explode(',', $this->_fields)} FROM {$this->_table}";

            if (count($this->_joins) > 0)
                $q .= " USING {$this->_table} JOIN " . implode(', ', $this->_joins);

            if (count($this->_conditions) > 0)
                $q .= " WHERE " . implode(' AND ', $this->_conditions);

            if (count($this->_orders) > 0)
                $q .= " ORDER BY " . implode(', ', $this->_orders);

            if (isset($this->_limit))
            {
                $q .= " LIMIT " . (is_array($this->_limit)
                        ? $this->_limit['offset'] . ',' . $this->_limit['count']
                        : $this->_limit);
            }

            return $q;
        }
    }
