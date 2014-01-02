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
     * @package    MuPHP\DB
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2013, Mathieu AMIOT
     * @version    0.1
     * @changelog
     *      0.1 : dev in progress
     */

    namespace MuPHP\DB;

    use MuPHP\DB\QueryGenerator\DBDeleteQueryGenerator;
    use MuPHP\DB\QueryGenerator\DBInsertQueryGenerator;
    use MuPHP\DB\QueryGenerator\DBSelectQueryGenerator;
    use MuPHP\DB\QueryGenerator\DBUpdateQueryGenerator;

    class FieldNotDefinedException extends \Exception
    {
        public function __construct($field)
        {
            parent::__construct("The field [$field] has not been defined in this model.");
        }
    }

    abstract class DBModel
    {
        const TABLE_NAME = null;
        static protected $__fieldsDefinition = array(
            'id' => array(
                'type'          => 'UNSIGNED INT',
                'allowNull'     => false,
                'primaryKey'    => true,
                'autoIncrement' => true
            )
        );
        static protected $_enableTimestamps = true;
        static private $__fieldDefaults = array(
            'type'          => 'VARCHAR(255)',
            'allowNull'     => true,
            'primaryKey'    => false,
            'autoIncrement' => false,
            'unique'        => false,
            'defaultValue'  => null,
            'comment'       => null,
            'values'        => null
        );
        static private $__timestampsDefinition = array(
            'created_at' => array(
                'type'         => 'TIMESTAMP',
                'allowNull'    => true,
                'defaultValue' => 'NULL'
            ),
            'updated_at' => array(
                'type'         => 'TIMESTAMP',
                'allowNull'    => true,
                'defaultValue' => 'NULL'
            )
        );
        private $_fields;
        private $_values;

        /**
         * Ctor
         */
        public function __construct()
        {
            self::_normalizeFields();
            $this->_fields = array_merge(self::$__fieldsDefinition, static::$__fieldsDefinition);

            $this->_values = array();
            foreach ($this->_fields as $fieldName => $spec)
                $this->_values[$fieldName] = $spec['defaultValue'];

            if (static::$_enableTimestamps)
            {
                $this->_fields = array_merge($this->_fields, static::$__timestampsDefinition);
                $this->created_at = time();
                $this->updated_at = null;
            }

            $this->id = null;
        }

        /**
         * Normalizes fields with default values
         */
        private static function _normalizeFields()
        {
            foreach (self::$__fieldsDefinition as &$definition)
                $definition = array_merge(self::$__fieldDefaults, $definition);

            if (static::$__fieldsDefinition !== self::$__fieldsDefinition)
                foreach (static::$__fieldsDefinition as &$definition)
                    $definition = array_merge(self::$__fieldDefaults, $definition);
        }

        /**
         * Finds a DAO-enabled object with given criteria
         * @param array|int|string $criteria
         */
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

        /**
         * Uncamelizes a string. MyExample => my_example
         * Used for ModelName => table_name translation here
         * @param $str
         * @return string
         */
        protected static function _uncamelize($str)
        {
            $str    = lcfirst($str);
            $lc     = strtolower($str);
            $result = '';
            $length = strlen($str);
            for ($i = 0; $i < $length; ++$i)
                $result .= ($str[$i] == $lc[$i] ? '' : '_') . $lc[$i];

            return $str;
        }

        /**
         * Creates a model initiated with given data
         * @param array $data
         */
        private static function _factoryWithData(array $data)
        {
            $class = get_called_class();
            $obj   = new $class();
            foreach ($data as $field => &$val)
                $obj->{$field} = $val;

            return $obj;
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
         * Instanciates an object + saves it instantly and then returns it
         * @param array $data
         * @return DBModel
         */
        public static function create(array $data)
        {
            /** @var DBModel $obj */
            $obj = static::_factoryWithData($data);
            $obj->save();

            return $obj;
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

        /**
         * Magic isset accessor
         * @param $name
         * @return bool
         */
        public function __isset($name)
        {
            return isset($this->_fields[$name]);
        }

        /**
         * Magic getter
         * @param $name
         * @return mixed
         * @throws FieldNotDefinedException
         */
        public function __get($name)
        {
            if (isset($this->_fields[$name]))
                return $this->_values[$name];

            throw new FieldNotDefinedException($name);
        }

        /**
         * Magic setter for values
         * @param $name
         * @param $value
         */
        public function __set($name, $value)
        {
            if (isset($this->_fields[$name]))
                $this->_values[$name] = $value;
        }

        /**
         * Saves current DAO model.
         * Inserts if new, or updates if already in DB.
         * @return $this
         */
        public function save()
        {
            $insert    = $this->id === null;
            $className = $insert ? "DBInsertQueryGenerator" : "DBUpdateQueryGenerator";
            /** @var DBInsertQueryGenerator|DBUpdateQueryGenerator $query */
            $query     = new $className(static::_tableName());
            foreach ($this->_values as $name => $var)
            {
                if ($name !== 'id')
                    $query->set($name, $var);
            }

            if (!$insert)
            {
                $query->where('id', '=', $this->id);
                if (static::$_enableTimestamps)
                {
                    $this->updated_at = time();
                    $query->set('updated_at', 'FROM_UNIXTIME('.$this->updated_at.')', false);
                }
            }
            else if (static::$_enableTimestamps)
            {
                $this->created_at = time();
                $query->set('created_at', 'FROM_UNIXTIME('.$this->created_at.')', false);
            }

            $query->run();

            if ($insert)
                $this->id = $query->getInsertId();

            return $this;
        }
    }
