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


    class DBDeleteQueryGenerator extends DBQueryGenerator
    {
        private $_conditions;

        /**
         * @param       $table
         * @param DBMan $db
         */
        public function __construct($table, DBMan $db = null)
        {
            parent::__construct($table, $db);
            $this->_conditions            = array();
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
         * @return string
         */
        public function getQuery()
        {
            $q = "DELETE FROM {$this->_table}";

            if (count($this->_conditions) > 0)
                $q .= " WHERE " . implode(' AND ', $this->_conditions);

            return $q;
        }
    }
