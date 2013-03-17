<?php

    namespace MuPHP\MVC;
    include_once __DIR__.'/../abstraction/designPatterns.php';
    require_once __DIR__.'/../users/userMan.php';
    require_once __DIR__.'/../db/dbMan.php';

    /**
     *
     */
    abstract class Module implements \MuPHP\DesignPatterns\Factory
    {
        protected
            $_fileName,
            $_tplVars,
            $_posted,
            $_actionCalled,
            $_i18n,
            $_headCanvas,
            $_footCanvas,
            $_modules;

        /**
         * Ctor
         */
        protected function __construct()
        {
            $classNS = explode('\\', strtolower(get_called_class()));
            $this->_fileName = $classNS[count($classNS) - 1];
            $this->_tplVars = array();
            $this->_actions = array();
            $this->_posted = false;
            $this->_actionCalled = 'index';
        }

        /**
         * @param $name
         * @return mixed
         * @throws \Exception
         */
        public static function factory($name)
        {
            if (file_exists(($fileName = __DIR__.'/../../_ctl/'.$name.'.php'))) include_once $fileName;
            else throw new \Exception('Module file not found! path='.$fileName);
            $className = '\MuPHP\MVC\\'.ucfirst($name);
            if (class_exists($className)) return new $className();
            else throw new \Exception('Module not found!');
        }

        /**
         * @param \MuPHP\Locales\localeLoader $i18n
         */
        public function setIntlEngine(\MuPHP\Locales\localeLoader &$i18n)
        {
            $this->_i18n = &$i18n;
        }

        /**
         * @param $fileName
         */
        public function setHeadCanvas($fileName)
        {
            $this->_headCanvas = $fileName;
        }

        /**
         * @param $fileName
         */
        public function setFootCanvas($fileName)
        {
            $this->_footCanvas = $fileName;
        }

        /**
         * @param $modules
         */
        public function setModulesArray(&$modules)
        {
            $this->_modules = $modules;
        }

        /**
         *
         */
        public function run()
        {
            $this->_updateState();
            $this->_compute();
            $this->_render();
        }

        function index()
        {
            // STUB
        }

        /**
         *
         */
        protected function _compute()
        {
            // STUB
            $this->index();
        }

        /**
         * @throws \Exception
         */
        protected function _render()
        {
            $this->_i18n->selectSection(\MuPHP\Locales\localeLoader::LOCALE_CONTENT);
            $this->_i18n->getPageNode($this->_fileName);
            $headFile = __DIR__.'/../../_tpl/canvas/' . $this->_headCanvas . '.phtml';
            if (file_exists($headFile))
                include $headFile;

            $tplName = __DIR__.'/../../_tpl/' . $this->_fileName . '.phtml';
            if (!file_exists($tplName))
                throw new \Exception('Template file not found! path='.$tplName);

            extract($this->_tplVars, EXTR_OVERWRITE|EXTR_REFS);
            include $tplName;

            $this->_i18n->selectSection(\MuPHP\Locales\localeLoader::LOCALE_FOOTER);
            $footFile = __DIR__.'/../../_tpl/canvas/' . $this->_footCanvas . '.phtml';
            if (file_exists($footFile))
                include $footFile;
        }

        /**
         * Updates form submitted state or not
         */
        private function _updateState()
        {
            $this->_posted = isset($_POST) && !empty($_POST);
        }

        /**
         * Outputs the menu of the website
         */
        protected function _buildFoundationMenu($version)
        {
            $menu = array();
            $localizedData = $this->_i18n->buildMenu();
            $classNS = explode('\\', strtolower(get_called_class()));
            $curModule = $classNS[count($classNS) - 1];

            $userAuthLevel = \MuPHP\Accounts\userMan::loggedIn() ? \MuPHP\Accounts\userMan::currentUser()->getAuthLevel() : null;

            foreach ($this->_modules as $url => $mod)
            {
                if (!isset($mod['menuItem'])
                || ($userAuthLevel === null && ($mod['registeredOnly'] || $mod['adminOnly']))
                || ($userAuthLevel === 'USER' && $mod['adminOnly']))
                    continue;

                $data = array(
                    'url' => $url,
                    'label' => $localizedData[$url],
                    'active' => $curModule === $url
                );

                if ($mod['menuItem']['parent'] === null)
                    $menu[$mod['menuItem']['position']] = $data;
                else
                    $menu[$mod['menuItem']['parent']]['children'][$mod['menuItem']['position']] = $data;
            }

            foreach ($menu as &$m)
            {
                if (isset($m['children']))
                    ksort($m['children']);
            }

            ksort($menu, SORT_ASC);

            switch ($version)
            {
                case 3:
                    $start = '<ul class="nav-bar vertical">';
                    $end = '</ul>';
                    break;
                case 4:
                    $start = '<ul class="side-nav">';
                    $end = '</ul>';
                    break;
                default:
                    $start = $end = '';
            }


            echo $start.$this->{'_outputMenuStringFoundation'.$version}($menu).$end;
        }

        /**
         * @param $menu
         * @return string
         */
        protected function _outputMenuStringFoundation4(&$menu)
        {
            $result = '<li class="divider"></li>'.PHP_EOL;
            for ($i = 0, $l = count($menu); $i < $l; ++$i)
            {
                $isActive = $menu[$i]['active'];
                $classString = ($isActive ? ' class="active"': '');

                $contents = "<li{$classString}><a href=\"{$menu[$i]['url']}\">{$menu[$i]['label']}</a></li>".PHP_EOL;
                /*if ($hasFlyout)
                {
                    $contents .= '<div class="content">';
                    $contents .= '</div>';
                    $contents .= '<a href="#" class="flyout-toggle"><span> </span></a>'.PHP_EOL;
                    $contents .= '<ul class="flyout">'.PHP_EOL;
                    $contents .= $this->_outputMenuStringFoundation4($menu[$i]['children']).PHP_EOL;
                    $contents .= '</ul>'.PHP_EOL;
                }*/

                $result .= $contents."<li class='divider'></li>".PHP_EOL;
            }

            return $result;
        }

        /**
         * @param $menu
         * @return string
         */
        protected function _outputMenuStringFoundation3(&$menu)
        {
            $result = '';
            for ($i = 0, $l = count($menu); $i < $l; ++$i)
            {
                $hasFlyout = isset($menu[$i]['children']);
                $isActive = $menu[$i]['active'];
                $classString = ($hasFlyout || $isActive ? ' class="'. ($hasFlyout ? 'has-flyout' : '').($hasFlyout && $isActive ? " " : '').($isActive ? 'active' : '') .'" ' : '');

                $contents = "<a href=\"{$menu[$i]['url']}\">{$menu[$i]['label']}</a>".PHP_EOL;
                if ($hasFlyout)
                {
                    $contents .= '<a href="#" class="flyout-toggle"><span> </span></a>'.PHP_EOL;
                    $contents .= '<ul class="flyout">'.PHP_EOL;
                    $contents .= $this->_outputMenuStringFoundation3($menu[$i]['children']).PHP_EOL;
                    $contents .= '</ul>'.PHP_EOL;
                }

                $result .= '<li'.$classString.'>'.$contents.'</li>'.PHP_EOL;
            }

            return $result;
        }

        /**
         * adds a variable to be used by the template
         * takes a variable number of string args, which represent the variable names
         * @param string $name
         * @param mixed &$var
         */
        protected function _templateVar($name, &$var)
        {
            $this->_tplVars[$name] = &$var;
        }
    }
