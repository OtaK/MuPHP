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
     * @package    MuPHP
     * @subpackage Hash
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2012, Mathieu AMIOT
     * @version    1.0
     * @changelog
     *      1.0 : initial release
     */
    namespace MuPHP\Hash;

    /**
     * @package    MuPHP
     * @subpackage Hash
     * @throws     \Exception
     * One-way ciphering engine to hash data with the help of a salt, can do many passes to encrypt data
     * Credits phpass, improved version for PHP5+ and better organization
     */
    class hashMan
    {
        private
            $_portableHashes,
            $_randomState,
            $_saltGenerator;

        public function __construct($iterationCount = 8, $portableHashes = false)
        {
            $this->_portableHashes = $portableHashes;
            $this->_randomState = microtime() . getmypid();
            $this->_saltGenerator = new saltGenerator($iterationCount);
        }

        private function _getRandomBytes($count)
        {
            $output = '';
            if (is_readable('/dev/urandom') && ($fh = @fopen('/dev/urandom', 'rb')))
            {
                $output = fread($fh, $count);
                fclose($fh);
            }

            if (strlen($output) < $count)
            {
                $output = '';
                for ($i = 0; $i < $count; $i += 16)
                {
                    $this->_randomState = md5(microtime() . $this->_randomState);
                    $output .= pack('H*', md5($this->_randomState));
                }
                $output = substr($output, 0, $count);
            }

            return $output;
        }

        /**
         * Encrypts some data
         * @param $data
         * @param $setting
         * @return string
         */
        private function _crypt($data, $setting)
        {
            $output = '*0';
            if (substr($setting, 0, 2) == $output)
                $output = '*1';

            $id = substr($setting, 0, 3);
            if ($id != '$P$' && $id != '$H$')
                return $output;

            $count_log2 = strpos($this->_saltGenerator->getitoa64(), $setting[3]);
            if ($count_log2 < 7 || $count_log2 > 30)
                return $output;

            $count = 1 << $count_log2;

            $salt = substr($setting, 4, 8);
            if (strlen($salt) !== 8) return $output;

            $hash = md5($salt . $data, true);
            do { $hash = md5($hash . $data, true); } while (--$count);
            $output = substr($setting, 0, 12);
            $output .= $this->_saltGenerator->encode64($hash, 16);

            return $output;
        }

        /**
         * Hashes some data with BCrypt / DES / generic hashing
         * @param $data
         * @return string
         */
        public function hashData($data)
        {
            $random = '';
            if (CRYPT_BLOWFISH == 1 && !$this->_portableHashes)
            {
                $random = $this->_getRandomBytes(16);
                $hash   = crypt($data, $this->_saltGenerator->blowfish($random));
                if (strlen($hash) == 60) return $hash;
            }

            if (CRYPT_EXT_DES == 1 && !$this->_portableHashes) // DES Hashing fallback if BCrypt is not available
            {
                if (strlen($random) < 3) $random = $this->_getRandomBytes(3);
                $hash = crypt($data, $this->_saltGenerator->extended($random));
                if (strlen($hash) == 20) return $hash;
            }

            if (strlen($random) < 6) $random = $this->_getRandomBytes(6); // Generic Hashing fallback
            $hash = $this->_crypt($data, $this->_saltGenerator->generic($random));
            if (strlen($hash) == 34) return $hash;

            return '*';
        }

        /**
         * Hashes the $clearData and checks if it matches the provided $dbHash
         * If it matches, returns true
         * @param $clearData
         * @param $dbHash
         * @return bool
         */
        public function checkData($clearData, $dbHash)
        {
            $hash = $this->_crypt($clearData, $dbHash);
            if ($hash[0] == '*') $hash = crypt($clearData, $dbHash);
            return $hash == $dbHash;
        }
    }

    /**
     * Helper class for the hashMan
     */
    class saltGenerator
    {
        private
            $_iterationCount,
            $_itoa64;

        /**
         * Ctor, sets original values for some things
         * @param int $iterationCount
         */
        public function __construct($iterationCount)
        {
            $this->setIterationCount($iterationCount);
            $this->_itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        }

        // Getters and setters
        public function getitoa64() { return $this->_itoa64; }
        public function getIterationCount() { return $this->_iterationCount; }
        public function setIterationCount($val)
        {
            // Check if number is  between 4 and 31 and a power of 2
            if ($val < 4 || $val > 31 && ($val && $val & ($val - 1) === 0))
                $this->_iterationCount = $val;
            else if (!isset($this->_iterationCount))
                $this->_iterationCount = 8; // Stub default if not set + incorrect value
        }

        /**
         * Generic fallback for hashing
         * @param $input
         * @return string
         */
        public function generic($input)
        {
            $output = '$P$';
            $output .= $this->_itoa64[min($this->_iterationCount + 5, 30)];
            $output .= $this->encode64($input, 6);

            return $output;
        }

        public function extended($input)
        {
            $count_log2 = min($this->_iterationCount + 8, 24);
            $count      = (1 << $count_log2) - 1;

            $output = '_';
            $output .= $this->_itoa64[$count & 0x3f];
            $output .= $this->_itoa64[($count >> 6) & 0x3f];
            $output .= $this->_itoa64[($count >> 12) & 0x3f];
            $output .= $this->_itoa64[($count >> 18) & 0x3f];

            $output .= $this->encode64($input, 3);

            return $output;
        }

        public function blowfish($input)
        {
            $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

            $phpMin537 = PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >= 3 && PHP_RELEASE_VERSION >= 7;
            $output = $phpMin537 ? '$2y$' : '$2a$';
            $output .= chr(ord('0') + $this->_iterationCount / 10);
            $output .= chr(ord('0') + $this->_iterationCount % 10);
            $output .= '$';

            $i = 0;
            do
            {
                $c1 = ord($input[$i++]);
                $output .= $itoa64[$c1 >> 2];
                $c1 = ($c1 & 0x03) << 4;
                if ($i >= 16)
                {
                    $output .= $itoa64[$c1];
                    break;
                }

                $c2 = ord($input[$i++]);
                $c1 |= $c2 >> 4;
                $output .= $itoa64[$c1];
                $c1 = ($c2 & 0x0f) << 2;

                $c2 = ord($input[$i++]);
                $c1 |= $c2 >> 6;
                $output .= $itoa64[$c1];
                $output .= $itoa64[$c2 & 0x3f];
            } while (1);

            return $output;
        }

        public function encode64($input, $count)
        {
            $output = '';
            $i      = 0;
            do
            {
                $value = ord($input[$i++]);
                $output .= $this->_itoa64[$value & 0x3f];
                if ($i < $count) $value |= ord($input[$i]) << 8;
                $output .= $this->_itoa64[($value >> 6) & 0x3f];
                if ($i++ >= $count) break;
                if ($i < $count) $value |= ord($input[$i]) << 16;
                $output .= $this->_itoa64[($value >> 12) & 0x3f];
                if ($i++ >= $count) break;
                $output .= $this->_itoa64[($value >> 18) & 0x3f];
            } while ($i < $count);

            return $output;
        }
    }
