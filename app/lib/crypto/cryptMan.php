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
	 * @subpackage Crypt
	 * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
	 * @copyright  Copyright (c) 2013, Mathieu AMIOT
	 * @version    1.3
	 * @changelog
     *      1.3 : Added init() method to check if MCrypt is installed
     *      1.2 : Introduced namespace use
	 *      1.1 : moved configuration constants to class constants instead of project-wide defines
	 *      1.0 : initial release
	 */
    namespace MuPHP\Crypto;

    class MCryptNotInstalledException extends \Exception
    {
        public function __construct()
        {
            $this->message = "MCrypt seems to be not installed yet on your PHP!";
        }
    }

    /*
     * To generate an IV, display this page in your browser
     */
    // live data encryption constants
    define('ENCRYPTION_DATA_IV', utf8_decode('DATA IV HERE'));
    define('ENCRYPTION_DATA_KEY', 'SECRET DATA KEY HERE');
    // db data encryption constants
    define('ENCRYPTION_DB_IV', utf8_decode('DB IV HERE'));
    define('ENCRYPTION_DB_KEY', 'SECRET DB KEY HERE');
    // WS data encryption constants
    define('ENCRYPTION_WS_IV', utf8_decode('DB WS HERE'));
    define('ENCRYPTION_WS_KEY', 'SECRET WS KEY HERE');

	/**
	 * @package    MuPHP
	 * @subpackage Crypt
	 * @throws     \Exception
	 * Encrypts/Decrypts data with a set of constants that defines the engine in use, private keys and so on.
	 * The main purpose is to have separate private keys for different parts of the website in use, allowing almost
	 * unbreakable security (except bruteforcing, but even if the live data gets cracked, the db is still secure).
	 */
	class CryptMan
	{
		// Class configuration consts
		const CRYPTMAN_MODE_DB = 1; // Mode when using encrypted data from DB
		const CRYPTMAN_MODE_DATA = 2; // Mode when using encrypted data from Live Website
        const CRYPTMAN_MODE_WS = 4; // Mode when using a secured Webservice
		const CRYPTMAN_CIPHER_ENGINE = MCRYPT_BLOWFISH; // Ciphering engine in use
		const CRYPTMAN_MCRYPT_MODE = MCRYPT_MODE_ECB; // Passthrough mode in use

        /**
         * Checks if MCrypt is installed
         * @static
         * @throws MCryptNotInstalledException
         */
        private static function init()
        {
            if (!function_exists('mcrypt_create_iv'))
                throw new MCryptNotInstalledException();
        }

		/**
		 * Decrypts some data
		 * @static
		 * @param string    $data data to decrypt
		 * @param int       $mode mode (DB/LiveData)
		 * @return string
         * @throws MCryptNotInstalledException
		 */
		public static function decrypt($data, $mode = self::CRYPTMAN_MODE_DB)
		{
            self::init();
			list($key, $iv) = self::getKeys($mode);
			return self::getDecryptedString($data, $key, $iv);
		}

		/**
		 * Static internal helper to decrypt
		 * @static
		 * @param string    $data
		 * @param string    $key
		 * @param string    $iv
		 * @return string
		 */
		private static function getDecryptedString($data, $key, $iv)
		{
			return rtrim(mcrypt_decrypt(self::CRYPTMAN_CIPHER_ENGINE, $key, $data, self::CRYPTMAN_MCRYPT_MODE, $iv), "\0");
		}


		/**
		 * Encrypts some data - static version
		 * @static
		 * @param string    $data   data to encrypt
		 * @param int       $mode   mode (DB/LiveData)
		 * @return string
         * @throws MCryptNotInstalledException
		 */
		public static function encrypt($data, $mode = self::CRYPTMAN_MODE_DB)
		{
            self::init();
			list($key, $iv) = self::getKeys($mode);
			return self::getEncryptedString($data, $key, $iv);
		}

		/**
		 * Static internal helper to encrypt
		 * @static
		 * @param string    $data
		 * @param string    $key
		 * @param string    $iv
		 * @return string
		 */
		private static function getEncryptedString($data, $key, $iv)
		{
			return mcrypt_encrypt(self::CRYPTMAN_CIPHER_ENGINE, $key, $data, self::CRYPTMAN_MCRYPT_MODE, $iv);
		}

		/**
		 * Static internal helper to get the Key/IV pair matching the mode
		 * @static
		 * @throws \Exception    if supplied $mode doesn't exist
		 * @param int   $mode   const above
		 * @return array        to be used like list($key, $iv) = self::getKeys($mode);
		 */
		private static function getKeys($mode = self::CRYPTMAN_MODE_DB)
		{
			switch ($mode)
			{
				case self::CRYPTMAN_MODE_DATA:
					$iv  = ENCRYPTION_DATA_IV;
					$key = ENCRYPTION_DATA_KEY;
				break;
				case self::CRYPTMAN_MODE_DB:
					$iv  = ENCRYPTION_DB_IV;
					$key = ENCRYPTION_DB_KEY;
				break;
                case self::CRYPTMAN_MODE_WS:
                    $iv  = ENCRYPTION_WS_IV;
                    $key = ENCRYPTION_WS_KEY;
                break;
				default:
					throw new \Exception('An unsupported mode has been supplied, exiting...', -1);
			}

			return array($key, $iv);
		}

        /**
         * Generates a random IV, helper function
         * @static
         * @return string
         * @throws MCryptNotInstalledException
         */
        public static function generateIV()
        {
            self::init();
            return utf8_encode(mcrypt_create_iv(mcrypt_get_iv_size(self::CRYPTMAN_CIPHER_ENGINE, self::CRYPTMAN_MCRYPT_MODE)));
        }
	}

    if (strpos($_SERVER['REQUEST_URI'], 'CryptMan.php') !== false)
        die(CryptMan::generateIV());
