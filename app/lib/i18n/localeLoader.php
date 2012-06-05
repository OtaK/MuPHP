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
     * @package    TakPHPLib
     * @subpackage Locales
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2012, Mathieu AMIOT
     * @version    0.1
     * @changelog
     *      0.1 : pre-version
     */
    namespace TakPHPLib\Locales;
    include_once __DIR__ . '/../../cfg/define.php';

    class LocaleNotFoundException extends \Exception
    {
        public function __construct()
        {
            $this->message = "The provided locale doesn't exist!";
        }
    }

    class DOMDocumentNotInstalledException extends \Exception
    {
        public function __construct()
        {
            $this->message = "The DOMDocument class is not installed!";
        }
    }


    class localeLoader
    {
        /** @var string */
        private $_filePath;

        /** @var \DOMDocument */
        private $_xmlFile;

        /** @var \DOMNodeList */
        private $_currentPage;

        /** @var bool */
        private $_loaded;

        /**
         * Ctor
         * @param string $locale
         * @throws LocaleNotFoundException
         * @throws DOMDocumentNotInstalledException
         */
        public function __construct($locale = DEFAULT_LOCALE)
        {
            if (!self::localeExists($locale))
            {
                $this->_loaded = false;
                throw new LocaleNotFoundException();
            }

            if (!class_exists('DOMDocument'))
            {
                $this->_loaded = false;
                throw new DOMDocumentNotInstalledException();
            }

            $this->_xmlFile = new \DOMDocument();
            $this->_xmlFile->load($this->_filePath);
            $this->_loaded = true;
            $this->_currentPage = null;
        }

        /**
         * Checks if provided locale exists
         * @static
         * @param string $locale
         * @return bool
         */
        public function localeExists($locale)
        {
            $this->_filePath = __DIR__.'/../../locales/'.$locale.'.xml';
            return file_exists($this->_filePath);
        }

        /**
         * @param $currentPage
         * @return \DOMNodeList
         */
        public function getPageNode($currentPage)
        {
            return !$this->_loaded ?: ($this->_currentPage = $this->_xmlFile->getElementById($currentPage)->getElementsByTagName('text'));
        }

        /**
         * Gets a text
         * @param $textId
         * @return string
         */
        public function getText($textId)
        {
            if (!$this->_loaded || !$this->_currentPage) return '';
            for ($i = 0, $l = $this->_currentPage->length; $i < $l && $this->_currentPage->item($i)->attributes->getNamedItem('id')->nodeValue != $textId; ++$i);
            return ($i < $l) ?: $this->_currentPage->item($i)->textContent;
        }
    }
