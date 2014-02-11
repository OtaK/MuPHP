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
     * @copyright  Copyright (c) 2014, Mathieu AMIOT
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
        static protected $__idField = 'id';
        static protected $__fieldsDefinition = array(
            'id' => array(
                'type'          => 'UNSIGNED INT',
                'allowNull'     => false,
                'primaryKey'    => true,
                'autoIncrement' => true
            )
        );
        static protected $__enableTimestamps = true;
        static private $__fieldDefaults = array(
            'type'          => 'VARCHAR(255)',
            'allowNull'     => true,
            'primaryKey'    => false,
            'autoIncrement' => false,
            'index'         => false,
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
        private $_dirty;
        private $_fields;
        private $_values;

        /**
         * Ctor
         */
        public function __construct()
        {
            $this->_fields = self::fields();

            $this->_values = array();
            foreach ($this->_fields as $fieldName => $spec)
                $this->_values[$fieldName] = $spec['defaultValue'];

            if (static::$__enableTimestamps)
            {
                $this->_fields    = array_merge($this->_fields, static::$__timestampsDefinition);
                $this->created_at = time();
                $this->updated_at = null;
            }

            $this->id     = null;
            $this->_dirty = true;
        }

        /**
         * Gets table fields definition
         * @return array
         */
        public static function fields()
        {
            self::_normalizeFields();

            return array_merge(self::$__fieldsDefinition, static::$__fieldsDefinition);
        }

        /**
         * Finds a DAO-enabled object with given criteria
         * @param array|int|string $criteria
         * @return DBModel
         */
        public static function find($criteria)
        {
            $query = new DBSelectQueryGenerator(static::tableName());

            if (static::$__enableTimestamps)
            {
                $query->select('UNIX_TIMESTAMP(created_at) AS `_created_at_ts`');
                $query->select('UNIX_TIMESTAMP(updated_at) AS `_updated_at_ts`');
            }

            if (is_array($criteria))
                $query->where(static::$__idField, '=', $criteria);
            else
            {
                foreach ($criteria as $field => $val)
                    $query->where($field, '=', $val);
            }

            $query->limit(1);

            $row = $query->run();

            if (static::$__enableTimestamps)
            {
                $row['updated_at'] = (int)$row['_updated_at_ts'];
                $row['created_at'] = (int)$row['_created_at_ts'];
                unset($row['_created_at_ts'], $row['_updated_at_ts']);
            }

            $obj         = static::_unpackModel($row);
            $obj->_dirty = false;

            return $obj;
        }

        /**
         * Gets linked table name for current class
         * @return string
         */
        public static function tableName()
        {
            if (static::TABLE_NAME !== null)
                return static::TABLE_NAME;

            $class = get_called_class();

            return self::Uncamelize(end($class));
        }

        /**
         * Returns a collection of DAO-enabled objects
         * @param array $criteria
         * @return array
         */
        public static function all(array $criteria = null)
        {
            $query = new DBSelectQueryGenerator(static::tableName());
            if (static::$__enableTimestamps)
            {
                $query->select('UNIX_TIMESTAMP(created_at) AS `_created_at_ts`');
                $query->select('UNIX_TIMESTAMP(updated_at) AS `_updated_at_ts`');
            }

            if ($criteria !== null)
                foreach ($criteria as $field => $val)
                    $query->where($field, '=', $val);

            $result = array();
            foreach ($query->run() as $row)
            {
                if (static::$__enableTimestamps)
                {
                    $row['updated_at'] = (int)$row['_updated_at_ts'];
                    $row['created_at'] = (int)$row['_created_at_ts'];
                    unset($row['_created_at_ts'], $row['_updated_at_ts']);
                }
                $obj         = static::_unpackModel($row);
                $obj->_dirty = false;
                $result[]    = $obj;
            }

            return $result;
        }

        /**
         * Deletes an id from the table
         * @param $id
         * @return bool
         */
        public static function destroy($id)
        {
            $query = new DBDeleteQueryGenerator(static::tableName());
            $query->where(static::$__idField, '=', $id);

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
            $obj = static::_unpackModel($data);
            $obj->save();

            return $obj;
        }

        /**
         * Instanciates an unsaved object
         * @param array $data
         * @return DBModel
         */
        public static function build(array $data)
        {
            return static::_unpackModel($data);
        }

        /**
         * Uncamelizes a string. MyExample => my_example
         * Used for ModelName => table_name translation here
         * @param $str
         * @return string
         */
        public static function Uncamelize($str)
        {
            $str    = lcfirst($str);
            $lc     = strtolower($str);
            $result = '';
            for ($i = 0, $l = strlen($str); $i < $l; ++$i)
                $result .= ($str[$i] === $lc[$i] ? '' : '_') . $lc[$i];

            return $result;
        }

        /**
         * Camelizes a string. my_example => MyExample
         * Used for table_name => ModelName translation here
         * @param $str
         * @return string
         */
        public static function Camelize($str)
        {
            return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
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
         * Creates a model initiated with given data
         * @param array $data hash of data to assign
         * @return DBModel
         */
        private static function _unpackModel(array $data)
        {
            $class = get_called_class();
            /** @var DBModel $obj */
            $obj   = new $class();
            $obj->_massAssign($data);

            return $obj;
        }

        /**
         * Mass assignement method with optional field assignement filtering
         * @param array $data hash of data to assign
         * @param array $fields optional list of fields to assign
         * @return $this
         */
        private function _massAssign(array $data, array $fields = array())
        {
            $fn = count($fields) > 0;
            foreach ($data as $field => &$val)
            {
                if ($fn && in_array($field, $fields, true))
                    continue;

                $this->{$field} = $val;
            }

            return $this;
        }


        /**
         * Saves current DAO model.
         * Inserts if new, or updates if already in DB.
         * @return $this
         */
        public function save()
        {
            $insert    = $this->{static::$__idField} === null;
            $className = $insert ? "DBInsertQueryGenerator" : "DBUpdateQueryGenerator";
            /** @var DBInsertQueryGenerator|DBUpdateQueryGenerator $query */
            $query = new $className(static::tableName());
            foreach ($this->_values as $name => $var)
            {
                if ($name !== static::$__idField)
                    $query->set($name, $var);
            }

            if (!$insert)
            {
                $query->where(static::$__idField, '=', $this->{static::$__idField});
                if (static::$__enableTimestamps)
                {
                    $this->updated_at = time();
                    $query->set('updated_at', 'FROM_UNIXTIME(' . $this->updated_at . ')', false);
                }
            }
            else if (static::$__enableTimestamps)
            {
                $this->created_at = time();
                $query->set('created_at', 'FROM_UNIXTIME(' . $this->created_at . ')', false);
            }

            $query->run();

            if ($insert)
                $this->{static::$__idField} = $query->getInsertId();

            $this->_dirty = false;

            return $this;
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
            {
                $this->_values[$name] = $value;
                $this->_dirty         = true;
            }
        }

        /**
         * Checks if the current DAO is dirty
         * @return bool
         */
        public function isDirty()
        {
            return $this->_dirty;
        }
    }
