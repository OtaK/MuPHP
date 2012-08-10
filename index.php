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

    // Appel de toutes les pages(Views et Controllers) pour construire le site
    include_once 'app/cfg/define.php'; // calling cfg file

    if (DEBUG)
    {
        $siteBenchmark = new \TakPHPLib\Performance\benchmarker();
        $siteBenchmark->start();
    }
    $modules = getModules();
    $acl = new \TakPHPLib\Auth\rightsMan($modules); // rights management object
    session_start();
    if (\TakPHPLib\Accounts\userMan::loggedIn())
        $currentLocale = \TakPHPLib\Accounts\userMan::currentUser()->getUserLocale();
    else
        $currentLocale = DEFAULT_LOCALE;

    $pageName = (!isset($_GET['module']) ? 'home' : addslashes($_GET['module'])); // null check & default page
    $i18n = new \TakPHPLib\Locales\localeLoader($currentLocale);

    if ($auth = $acl->isAuthorized($pageName))
    {
        //\TakPHPLib\Utils\utils::safeInclude(__DIR__.'/app/_ctl/'.$modules[$pageName]['fileName'].'.php'); // model / controller
        include __DIR__.'/app/_ctl/'.$modules[$pageName]['fileName'].'.php'; // model / controller
        $headCanvas = $modules[$pageName]['headCanvas'];
        $footCanvas = $modules[$pageName]['footCanvas'];

        //$i18n->selectSection(\TakPHPLib\Locales\localeLoader::LOCALE_HEADER);
        $i18n->selectSection(\TakPHPLib\Locales\localeLoader::LOCALE_CONTENT);
        $i18n->getPageNode($pageName); // translations
        include __DIR__.'/app/_tpl/canvas/'.$headCanvas.'.phtml'; // header

        if (file_exists(__DIR__.'/app/_tpl/'.$modules[$pageName]['fileName'].'.phtml')) // view
        {
            include __DIR__.'/app/_tpl/'.$modules[$pageName]['fileName'].'.phtml';
        }

        $i18n->selectSection(\TakPHPLib\Locales\localeLoader::LOCALE_FOOTER);
        include __DIR__.'/app/_tpl/canvas/'.$footCanvas.'.phtml'; // footer

        /*\TakPHPLib\Utils\utils::safeInclude(__DIR__.'/app/_tpl/canvas/'.$headCanvas.'.phtml'); // header
        \TakPHPLib\Utils\utils::safeInclude(__DIR__.'/app/_tpl/'.$modules[$pageName]['fileName'].'.phtml'); // view
        \TakPHPLib\Utils\utils::safeInclude(__DIR__.'/app/_tpl/canvas/'.$footCanvas.'.phtml'); // footer*/
    }
    else // error if hacker detected
        header('Location: '.BASE_URL); // Redirect to home / landing

    if (DEBUG)
    {
        /** @var $siteBenchmark \TakPHPLib\Performance\benchmarker */
        $siteBenchmark->end();
        echo '<script type="text/javascript">$(\'#benchmark\').html(\''.$siteBenchmark->output('Generation', false).'\')</script>';
    }