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
        public static function sync($folder, $force = false)
        {
            if (!file_exists($folder))
                return false;

            foreach (glob(realpath($folder).'/*.php') as $file)
            {
                if ($file[0] === '.' || $file[0] === '_') continue;
                require($file);
                $class = substr($file, 0, strpos($file, '.'));
                $fields = $class::fields();
                $table = $class::tableName();
                self::_createTable($table, $fields, $force);
            }

            return true;
        }

        private static function _createTable($name, $spec, $force = false)
        {
            $q = '';
            if ($force)
                $q .= "DROP TABLE $name; ".PHP_EOL;
            $q .= "CREATE TABLE IF EXISTS $name (".PHP_EOL;
            foreach ($spec as $field => $fieldSpec)
            {
                $q .= "$field {$fieldSpec['type']}";
                if ($fieldSpec['type'] === 'ENUM')
                {
                    $enumSpec = implode("','", $fieldSpec['values']);
                    $q .= "('$enumSpec')";
                }
                $q .= $fieldSpec['defaultValue'] === null ? '' : ' DEFAULT '.$fieldSpec['defaultValue'];
                $q .= $fieldSpec['allowNull'] ? ' NULL' : ' NOT NULL';
                if ($fieldSpec['primaryKey'])
                {
                    $q .= ' PRIMARY KEY';
                    if ($fieldSpec['autoIncrement'])
                        $q .= ' AUTO_INCREMENT';
                }
                else if ($fieldSpec['unique'])
                    $q .= ' UNIQUE';

                $q .= $fieldSpec['comment'] === null ? '' : ' COMMENT "'.$fieldSpec['comment'].'"';
                $q .= ','.PHP_EOL;
            }
            $q = substr($q, 0, -2).PHP_EOL;
            $q .= ") ENGINE=InnoDB;";

            DBMan::get_instance()->multi_query($q);
        }
    }
