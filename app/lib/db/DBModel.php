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
     * @version    0.1
     * @changelog
     *      0.1 : dev in progress
     */

    namespace MuPHP\DB;


    abstract class DBModel
    {
        public $id;
        public $created_at;
        public $updated_at;

        const TABLE_NAME = null;

        public function __construct()
        {
            $this->id = null;
            $this->created_at = time();
            $this->updated_at = time();
        }

        /**
         * Gets linked table name for current class
         * @return string
         */
        protected static function _tableName()
        {
            if (static::TABLE_NAME !== null)
                return static::TABLE_NAME;

            $class = get_called_class();
            return self::_uncamelize(end($class));
        }

        private static function _factoryWithData(array $data)
        {
            $class = get_called_class();
            $obj = new $class();
            foreach ($data as $field => &$val)
                $obj->{$field} = $val;
        }

        public static function find($criteria)
        {
            $query = new DBSelectQueryGenerator(static::_tableName());
            if (is_array($criteria))
                $query->where('id', '=', $criteria);
            else
            {
                foreach ($criteria as $field => $val)
                    $query->where($field, '=', $val);
            }

            $query->limit(1);

            return static::_factoryWithData($query->run());
        }

        /**
         * Returns a collection of DAO-enabled objects
         * @param array $criteria
         * @return array
         */
        public static function all(array $criteria = null)
        {
            $query = new DBSelectQueryGenerator(static::_tableName());
            if ($criteria !== null)
                foreach ($criteria as $field => $val)
                    $query->where($field, '=', $val);

            $result = array();
            foreach ($query->run() as $row)
                $result[] = static::_factoryWithData($row);
            return $result;
        }

        /**
         * Deletes an id from the table
         * @param $id
         * @return bool
         */
        public static function destroy($id)
        {
            $query = new DBDeleteQueryGenerator(static::_tableName());
            $query->where('id', '=', $id);
            return $query->run();
        }

        /**
         * Saves current DAO model.
         * Inserts if new, or updates if already in DB.
         * @return $this
         */
        public function save()
        {
            $insert = $this->id === null;
            $className = $insert ? "DBInsertQueryGenerator" : "DBUpdateQueryGenerator";
            $query = new $className(static::_tableName());
            foreach (get_object_vars($this) as $name => $var)
            {
                if ($name[0] === '_' && $name[1] === '_')
                    continue;
                $query->set($name, $var);
            }

            if (!$insert)
                $query->where('id', '=', $this->id);

            $query->run();

            if ($insert)
                $this->id = $query->getInsertId();

            return $this;
        }

        /**
         * Uncamelizes a string. MyExample => my_example
         * Used for ModelName => table_name translation here
         * @param $str
         * @return string
         */
        protected static function _uncamelize($str)
        {
            $str = lcfirst($str);
            $lc = strtolower($str);
            $result = '';
            $length = strlen($str);
            for ($i = 0; $i < $length; ++$i)
                $result .= ($str[$i] == $lc[$i] ? '' : '_') . $lc[$i];
            return $str;
        }

        /**
         * Camelizes a string. my_example => MyExample
         * Used for table_name => ModelName translation here
         * @param $str
         * @return string
         */
        protected static function _camelize($str)
        {
            return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
        }
    }