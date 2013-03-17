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
    namespace MuPHP\Files;

    class FileNotFoundException extends \Exception
    {
        public function __construct()
        {
            $this->message = 'File not found in $_FILES[] array!';
        }
    }

    class FileUploadErrorException extends \Exception
    {
        public function __construct()
        {
            $this->message = 'An error occured during file upload!';
        }
    }

    class FileTypeMismatchException extends \Exception
    {
        public function __construct()
        {
            $this->message = 'File types are not matched!';
        }
    }

    /**
     * @package    MuPHP
     * @subpackage Files
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2013, Mathieu AMIOT
     * @version    1.0
     * @changelog
     *      1.0 : initial release
     */
    class fileUploader
    {
        const
            UPLOAD_PATH     = '/media/uploads/',
            TYPEAPPLICATION = 'application/',
            TYPEAUDIO       = 'audio/',
            TYPEIMAGE       = 'image/',
            TYPEMESSAGE     = 'message/',
            TYPEMODEL       = 'model/',
            TYPEMULTIPART   = 'multipart/',
            TYPEOTHER       = 'other/',
            TYPETEXT        = 'text/',
            TYPEVIDEO       = 'video/',
            TYPEANY         = null;

        protected
            $_fileInfo,
            $_fileTypeMatches;

        /**
         * Ctor
         */
        public function __construct()
        {
            $this->_fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        }

        /**
         * @param string    $postName
         * @param int       $wantedType
         * @throws FileTypeMismatchException
         * @throws FileNotFoundException
         * @throws FileUploadErrorException
         */
        public function uploadFile($postName, $wantedType = self::TYPEANY)
        {
            if (!isset($_FILES[$postName]))
                throw new FileNotFoundException();

            $fileType = $this->_getType($_FILES[$postName]['tmp_name']);
            if ($wantedType !== self::TYPEANY && !$this->_checkType($fileType))
                throw new FileTypeMismatchException();

            $uploadRes = move_uploaded_file(
                $_FILES[$postName]['tmp_name'],
                self::UPLOAD_PATH . $_FILES[$postName]['name'] . ${sha1_file($_FILES[$postName]['tmp_name'])}
            );

            if (!$uploadRes)
                throw new FileUploadErrorException();
        }

        /**
         * @param $fileName
         * @return string
         */
        protected function _getType($fileName)
        {
            return $this->_fileInfo->file($fileName);
        }

        protected function _checkType($fileType)
        {
            if (strpos($fileType, self::TYPEAPPLICATION) !== false) return true;
            if (strpos($fileType, self::TYPEAUDIO) !== false) return true;
            if (strpos($fileType, self::TYPEIMAGE) !== false) return true;
            if (strpos($fileType, self::TYPEMESSAGE) !== false) return true;
            if (strpos($fileType, self::TYPEMODEL) !== false) return true;
            if (strpos($fileType, self::TYPEMULTIPART) !== false) return true;
            if (strpos($fileType, self::TYPETEXT) !== false) return true;
            if (strpos($fileType, self::TYPEVIDEO) !== false) return true;
            return false;
        }
    }
