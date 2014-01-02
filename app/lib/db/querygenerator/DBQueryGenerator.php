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
    use MuPHP\DB\DBResult;

    abstract class DBQueryGenerator
    {
        protected $_db;
        protected $_table;

        public function __construct($table, DBMan $db = null)
        {
            $this->_table = $table;
            $this->_db    = $db ? : DBMan::get_instance();
        }

        /**
         * @return array|bool|DBResult
         */
        public function run()
        {
            $res = $this->_db->query($this->getQuery());
            if ($res->num_rows === 1)
                return $res->fetch_assoc();

            return $res;
        }

        /**
         * @return mixed
         */
        public function getInsertId()
        {
            return $this->_db->insert_id;
        }

        /**
         * @return string
         */
        abstract public function getQuery();
    }
