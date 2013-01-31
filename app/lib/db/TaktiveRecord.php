<?php

    /*
     * Copyright 2012 Mathieu "OtaK_" Amiot <m.amiot@otak-arts.com> http://mathieu-amiot.fr/
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
     * @package MuPHP
     * @subpackage ActiveRecord
     * @author Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright Copyright (c) 2012, Mathieu AMIOT
     * @version 0.1dev
     * @changelog
     *      0.1dev : In dev
     */
    namespace MuPHP\ActiveRecord;
    require_once __DIR__.'/dbMan.php';

    class TaktiveRecordSchema
    {
        private
            $_schema,
            $_db;

        public function __construct()
        {
            $this->_db = \MuPHP\DB\dbMan::get_instance();
            $this->_db->autocommit(false);
            $this->buildSchema();
        }

        public function buildSchema()
        {
            $this->_schema = array(); // reset if needed
            $tables = $this->_db->singleColumnQuery("SHOW TABLES");
            for ($i = 0, $il = count($tables); $i < $il; ++$i)
            {
                $this->_schema[$tables[$i]] = array();
                $fields = $this->_db->query("SHOW COLUMNS FROM %s", array($tables[$i]));
                while ($curField = $fields->fetch_assoc())
                    $this->_schema[$tables[$i]][$curField['Field']] = dbField::constructFromShowColumns($tables[$i], $curField);
            }
        }
    }

    /**
     * @property string $table
     * @property string $fieldName
     * @property string $type
     * @property bool   $nullAllowed
     * @property int    $keyType
     */
    class dbField
    {
        private
            /** @var dbField $_previousState */
            $_previousState,
            $_table,
            $_fieldName,
            $_type,
            $_nullAllowed,
            $_keyType;

        const
            ORDINARY_FIELD = '',
            UNIQUE_FIELD = 'UNIQUE',
            PRIMARY_KEY = 'PRIMARY KEY';

        static public function constructFromArgList($table = '', $name = '', $type = 'int', $null = false, $keyType = self::ORDINARY_FIELD)
        {
            return new dbField($table, $name, $type, $null, $keyType);
        }

        static public function constructFromShowColumns($table = '', array $fieldSchema)
        {
            return new dbField($table, $fieldSchema['Field'], $fieldSchema['Type'], $fieldSchema['Null'] == 'YES', self::_keyTypeFromShowColumns($fieldSchema['Key']));
        }

        static private function _keyTypeFromShowColumns($fieldType)
        {
            switch ($fieldType)
            {
                case 'PRI':
                    return self::PRIMARY_KEY;
                case 'UNI':
                    return self::UNIQUE_FIELD;
            }
            return self::ORDINARY_FIELD;
        }

        private function __construct($table = '', $name = '', $type = 'int', $null = false, $keyType = self::ORDINARY_FIELD)
        {
            $this->_table           = $table;
            $this->_fieldName       = $name;
            $this->_type            = $type;
            $this->_nullAllowed     = $null;
            $this->_keyType         = $keyType;
        }

        public function save() { \MuPHP\DB\dbMan::get_instance()->commit(); $this->_saveCurrentState(); }

        private function _saveCurrentState()
        {
            if (isset($this->_previousState))
                unset($this->_previousState);
            $this->_previousState = clone $this;
        }

        private function _restorePreviousState()
        {
            $this->_fieldName       = $this->_previousState->fieldName;
            $this->_keyType         = $this->_previousState->keyType;
            $this->_nullAllowed     = $this->_previousState->nullAllowed;
            $this->_table           = $this->_previousState->table;
            $this->_type            = $this->_previousState->type;
        }

        public function __get($name){ return $this->{'_'.$name}; }
        public function __set($name, $value)
        {
            $var = &$this->{'_'.$name};
            if ($var == $value) return;

            $prevName = $this->_fieldName;
            if ($name == 'fieldName')
                $prevName = $this->_previousState->fieldName;

            $var = $value;
            \MuPHP\DB\dbMan::get_instance()->query("
                ALTER TABLE %s
                CHANGE %s %s %s %s %s",
                array(
                    $this->_table,
                    $prevName,
                    $this->_fieldName,
                    $this->_type,
                    $this->_nullAllowed ? 'NULL' : 'NOT NULL',
                    $this->_keyType
                )
            );
        }
    }

    class dbRecord implements \Iterator
    {
        private
            $_field,
            $_curData,
            $_queryResult;

        /**
         * Ctor
         * @param dbField $field
         * @param \MuPHP\DB\dbResult $res
         */
        public function __construct(dbField &$field, \MuPHP\DB\dbResult &$res)
        {
            $this->_queryResult = $res;
            $this->_curData = $this->_queryResult->fetch_assoc();
            $this->_field = $field;
        }

        /**
         * (PHP 5 &gt;= 5.1.0)<br/>
         * Return the current element
         * @link http://php.net/manual/en/iterator.current.php
         * @return mixed Can return any type.
         */
        public function current() { return $this->_curData; }

        /**
         * (PHP 5 &gt;= 5.1.0)<br/>
         * Move forward to next element
         * @link http://php.net/manual/en/iterator.next.php
         * @return void Any returned value is ignored.
         */
        public function next() { $this->_curData = $this->_queryResult->fetch_assoc(); }

        /**
         * (PHP 5 &gt;= 5.1.0)<br/>
         * Return the key of the current element
         * @link http://php.net/manual/en/iterator.key.php
         * @return int scalar on success, integer
         * 0 on failure.
         */
        public function key() { return $this->_queryResult->getRecordNumber(); }

        /**
         * (PHP 5 &gt;= 5.1.0)<br/>
         * Checks if current position is valid
         * @link http://php.net/manual/en/iterator.valid.php
         * @return boolean The return value will be casted to boolean and then evaluated.
         *       Returns true on success or false on failure.
         */
        public function valid() { return (bool)$this->_curData; }

        /**
         * (PHP 5 &gt;= 5.1.0)<br/>
         * Rewind the Iterator to the first element
         * @link http://php.net/manual/en/iterator.rewind.php
         * @return void Any returned value is ignored.
         */
        public function rewind() { $this->_queryResult->first(); $this->next(); }
    }
