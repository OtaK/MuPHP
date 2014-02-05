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
     *             0.1 : Dev in progress
     */
    namespace MuPHP\DB;


    class DBMap
    {
        /**
         * Syncs all models found in given folder - creates tables with spec
         * Specify $force to true to automatically drop tables
         * @param string $folder folder in which models can be found
         * @param bool   $force  if true, drops the tables beforehand
         * @return bool
         */
        public static function sync($folder, $force = false)
        {
            if (!file_exists($folder))
                return false;

            foreach (glob(realpath($folder) . '/*.php') as $file)
            {
                if ($file[0] === '.' || $file[0] === '_') continue;
                require($file);
                $class  = substr($file, 0, strpos($file, '.'));
                $fields = $class::fields();
                $table  = $class::tableName();
                self::_createTable($table, $fields, $force);
            }

            return true;
        }

        /**
         * Reverse sync - Creates PHP models from MySQL DB Schema (SHOW TABLES/FIELDS)
         * @param $destinationFolder
         */
        public static function reverseSync($destinationFolder)
        {
            if (!file_exists($destinationFolder))
                mkdir($destinationFolder, 0775, true);

            $tables = DBMan::get_instance()->query("SHOW TABLES");
            $tables->setIterationMode(DBResult::ITERATE_NUM);
            foreach ($tables as $table)
            {
                $tableName = $table[0];
                $modelSpec = self::_getSpecFromShowTable($tableName);
                $pk = self::_extractPrimaryKeyFromSpec($modelSpec);
                self::_writeModelSpec($destinationFolder, $tableName, $modelSpec, $pk);
            }
        }

        /**
         * Return spec's primary key
         * @param array $tableSpec
         * @return null|string
         */
        private static function _extractPrimaryKeyFromSpec(array $tableSpec)
        {
            foreach ($tableSpec as $field => $spec)
                if ($spec['primaryKey'])
                    return $field;

            return null;
        }

        /**
         * Parses raw SQL table fields to API-compatible table spec
         * @param array $tableName
         * @return array
         */
        private static function _getSpecFromShowTable(array $tableName)
        {
            $fields = DBMan::get_instance()->query("SHOW COLUMNS FROM %s", array($tableName));
            $spec   = array();
            foreach ($fields as $f)
                $spec[$f['Field']] = static::_getSpecFromShowField($f);

            return $spec;
        }

        /**
         * Parses raw SQL field data to API-compatible field spec
         * @param array $fieldData
         * @return array
         */
        private static function _getSpecFromShowField(array $fieldData)
        {
            $spec = array(
                'type'       => $fieldData['Type'],
                'allowNull'  => $fieldData['Null'] === 'YES',
                'defaultValue' => $fieldData['Default']
            );

            switch ($fieldData['Key'])
            {
                case 'PRI':
                    $spec['primaryKey'] = true;
                    break;
                case 'MUL':
                    $spec['index'] = true;
                    break;
                case 'UNI':
                    $spec['unique'] = true;
                    break;
            }

            if ($spec['primaryKey'] && strpos($fieldData['Extra'], 'auto_increment') !== false)
                $spec['autoIncrement'] = true;

            return $spec;
        }

        /**
         * Creates a table with the following spec
         * @param string $name  table name
         * @param array  $spec  spec array
         * @param bool   $force if true, drops the tables beforehand
         */
        private static function _createTable($name, array $spec, $force = false)
        {
            $fieldsSpecification = array();
            foreach ($spec as $field => $fieldSpec)
            {
                $fs = "$field {$fieldSpec['type']}";
                if ($fieldSpec['type'] === 'ENUM')
                {
                    $enumSpec = implode("','", $fieldSpec['values']);
                    $fs .= "('$enumSpec')";
                }
                $fs .= $fieldSpec['defaultValue'] === null ? '' : ' DEFAULT ' . $fieldSpec['defaultValue'];
                $fs .= $fieldSpec['allowNull'] ? ' NULL' : ' NOT NULL';
                if ($fieldSpec['primaryKey'])
                {
                    $fs .= ' PRIMARY KEY';
                    if ($fieldSpec['autoIncrement'])
                        $fs .= ' AUTO_INCREMENT';
                }
                else if ($fieldSpec['unique'])
                    $fs .= ' UNIQUE';
                else if ($fieldSpec['index'])
                    $fs .= ' INDEX';

                $fs .= $fieldSpec['comment'] === null ? '' : ' COMMENT "' . $fieldSpec['comment'] . '"';
                $fieldsSpecification[] = $fs;
            }

            $q = '';
            if ($force)
                DBMan::get_instance()->query("DROP TABLE $name");
            $q .= "CREATE TABLE IF EXISTS $name (" . PHP_EOL . implode(',' . PHP_EOL, $fieldsSpecification) . ") ENGINE=InnoDB;";

            DBMan::get_instance()->query($q);
        }

        /**
         * Writes a model spec to folder
         * @param $folder
         * @param $tableName
         * @param $modelSpec
         * @param $pk
         * @todo
         */
        private static function _writeModelSpec($folder, $tableName, $modelSpec, $pk)
        {

        }
    }
