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
         * @param bool   $force if true, drops the tables beforehand
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
                $fields = DBMan::get_instance()->query("SHOW FIELDS FROM %s", array($table[0]));
                $spec = array();
                foreach ($fields as $f)
                    $spec[$f['Field']] = static::_getSpecFromShowField($f);
            }
        }

        private static function _getSpecFromShowField(array $data)
        {
            // TODO return spec from db fields
        }

        /**
         * Creates a table with the following spec
         * @param string $name table name
         * @param array  $spec spec array
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

                $fs .= $fieldSpec['comment'] === null ? '' : ' COMMENT "' . $fieldSpec['comment'] . '"';
                $fieldsSpecification[] = $fs;
            }

            $q = '';
            if ($force)
                $q .= "DROP TABLE $name; " . PHP_EOL;
            $q .= "CREATE TABLE IF EXISTS $name (" . PHP_EOL . implode(',' . PHP_EOL, $fieldsSpecification) . ") ENGINE=InnoDB;";

            DBMan::get_instance()->multi_query($q);
        }
    }
