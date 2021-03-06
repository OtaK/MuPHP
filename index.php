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

    if (PHP_SAPI === 'cli') // if called from CLI, serve as a generator console
    {
        if (!isset($argv))
            $argv = &$_SERVER['argv'];
        require_once __DIR__.'/app/lib/cli/Console.php';
        \MuPHP\CLI\Console::boot($argv);
        die('Bye!');
    }

    // Calling all Controllers and Views to build the website
    include_once 'app/cfg/define.php'; // calling cfg file

    if (DEBUG)
    {
        $siteBenchmark = new \MuPHP\Performance\MuBenchmarker();
        $siteBenchmark->start();
    }

    $modules = &\MuPHP\MVC\Module::getModules();
    $acl = new \MuPHP\Users\RightsMan($modules); // rights management object
    @session_start();
    $currentLocale = \MuPHP\Users\UserMan::loggedIn() ? \MuPHP\Users\UserMan::currentUser()->getUserLocale() : DEFAULT_LOCALE;

    $pageName = (!isset($_GET['module']) ? 'home' : addslashes($_GET['module'])); // null check & default page
    $i18n = new \MuPHP\i18n\LocaleLoader($currentLocale);
    $locales = getLocales();

    if ($acl->isAuthorized($pageName))
    {
        $module = \MuPHP\MVC\Module::factory($pageName);
        $module->setIntlEngine($i18n);
        $module->run();
    }
    else // error if hacker detected
        header('Location: '.BASE_URL); // Redirect to home / landing

    if (DEBUG)
    {
        /** @var $siteBenchmark \MuPHP\Performance\MuBenchmarker */
        $siteBenchmark->end();
        echo '<script type="text/javascript">document.getElementById(\'benchmark\').innerHTML = \''.$siteBenchmark->output('Generation', false).'\';</script>';
    }
