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
     * @subpackage Locales
     * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
     * @copyright  Copyright (c) 2012, Mathieu AMIOT
     * @version    0.5a
     * @changelog
     *      0.5a : first stable alpha
     *      0.1 : pre-version
     */
    namespace MuPHP\Locales;
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

    class SectionNotFoundException extends \Exception
    {
        public function __construct()
        {
            $this->message = "The demanded section could not be found!";
        }
    }


    class localeLoader
    {
        const
            LOCALE_HEADER = 'header',
            LOCALE_CONTENT = 'content',
            LOCALE_DIALOGS = 'dialogs',
            LOCALE_FOOTER = 'footer';

        /** @var string */
        private $_filePath;

        /** @var \DOMDocument */
        private $_xmlFile;

        /** @var \DOMNodeList */
        private $_currentPage;

        /** @var string */
        private $_currentPageTitle;

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
            if (!self::localeExists(substr($locale, 0, 2)))
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
         * @param $locale
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
            if (!$this->_loaded) return new \DOMNodeList();
            $page = $this->_xmlFile->getElementById($currentPage);
            if (!$page) return new \DOMNodeList();
            $this->_currentPageTitle = $page->getAttribute('title');
            return ($this->_currentPage = $page->getElementsByTagName('text'));
        }

        /**
         * Gets a text
         * @param $textId
         * @return string
         */
        public function getText($textId)
        {
            if (!$this->_loaded || !$this->_currentPage) return '';
            for ($i = 0, $l = $this->_currentPage->length; $i < $l; ++$i)
            {
                if (!($this->_currentPage->item($i) instanceof \DOMElement))
                    continue;
                if ($this->_currentPage->item($i)->attributes->getNamedItem('name')->nodeValue == $textId)
                    break;
            }
            return ($i == $l) ? '' : $this->_currentPage->item($i)->textContent;
        }

        /**
         * Builds current page node as associative array
         * @return array
         */
        public function buildPageArray()
        {
            if (!$this->_loaded || !$this->_currentPage) return array();

            $res = array();
            for ($i = 0, $l = $this->_currentPage->length; $i < $l; ++$i)
            {
                if (!($this->_currentPage->item($i) instanceof \DOMElement)) continue;
                $k = $this->_currentPage->item($i)->attributes->getNamedItem('name')->nodeValue;
                $res[$k] = $this->_currentPage->item($i)->textContent;
            }
            return $res;
        }

        /**
         * Selects a section in the current XML file
         * @param string $section
         * @param bool   $return
         * @throws SectionNotFoundException
         * @return \DOMNodeList
         */
        public function selectSection($section = self::LOCALE_CONTENT, $return = false)
        {
            if ($res = $this->_xmlFile->getElementsByTagName($section))
            {
                $this->_loaded = true;
                if ($return)
                    return $res->item(0)->childNodes;
                $this->_currentPage = $res->item(0)->childNodes;
                return null;
            }
            else
                throw new SectionNotFoundException();
        }

        /**
         * Builds a localized menu array
         * @return array
         */
        public function buildMenu()
        {
            $res = array();
            $pages = $this->selectSection(self::LOCALE_CONTENT, true);
            for ($i = 0, $l = $pages->length; $i < $l; ++$i)
            {
                $curItem = $pages->item($i);
                if (!($curItem instanceof \DOMElement)) continue;
                /** @var $curItem \DOMElement */
                $id = $curItem->getAttribute('xml:id');
                $label = $curItem->getAttribute('menulabel');
                $res[$id] = $label;
            }
            return $res;
        }

        /**
         * Gets the current page title
         * @return string
         */
        public function getPageTitle()
        {
            return (isset($this->_currentPageTitle) ? $this->_currentPageTitle : '');
        }

        /**
         * Returns a given element id in the xml file
         * @param $textId
         *
         * @return string
         */
        public function getGlobalElemText($textId)
        {
            return ($tmp = $this->_xmlFile->getElementById($textId)) ? $tmp->textContent : '';
        }
    }
