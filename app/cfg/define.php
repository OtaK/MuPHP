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

    // Base includes
    include_once __DIR__ . '/modules.php';
    if (!defined('AUTOLOAD') || AUTOLOAD)
    {
        include_once __DIR__ . '/autoloader.php';
        MuPHP_autoload();
    }
    else
    {
        include_once __DIR__ . '/../lib/users/userMan.php';
        include_once __DIR__ . '/../lib/i18n/localeLoader.php';
        include_once __DIR__ . '/../lib/utils/utils.php';
    }
    /*include_once __DIR__ . '/../lib/users/rightsMan.php';
    include_once __DIR__ . '/../lib/users/userMan.php';
    include_once __DIR__ . '/../lib/db/dbMan.php';
    include_once __DIR__ . '/../lib/i18n/localeLoader.php';*/

    // DB & Site Path Constants
    if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') // Development site
    {
        define('DEBUG', true);
        define('DBHOST', 'localhost');
        define('DBUSER', 'root');
        define('DBPWD', '');
        define('DBBASE', '');
        $sitePath = '/MuPHP/'; // Change this according to your environment
    }
    else // Production constants
    {
        define('DEBUG', false);
        define('DBHOST', '');
        define('DBUSER', '');
        define('DBPWD', '');
        define('DBBASE', '');
        $sitePath = '/'; // And change this too, but it should not, if you're at the root of your domain
    }

    // Locale definition
    define('DEFAULT_LOCALE', 'fr_FR'); // i18n file to call

    $currentLocale = DEFAULT_LOCALE; // Runtime locale definitions
    if (\MuPHP\Accounts\userMan::loggedIn())
        $currentLocale = \MuPHP\Accounts\userMan::currentUser()->getUserLocale();

    $shortLocale = substr($currentLocale, 0, 2);
    define('CURRENT_LOCALE', $currentLocale);
    define('SHORT_LOCALE', $shortLocale);

    setlocale(LC_TIME, $currentLocale.'.UTF8'); // Locale definition for time expression

    // Website constants
    define('SITE_PATH', $sitePath); // Real path to website root
    define('SITE_NAME', 'MuPHP'); // Displayed site name
    define('IS_HTTPS', $_SERVER['SERVER_PORT'] == '443'); // Boolean telling if https is currently used
    define('SITE_PORT', ($_SERVER['SERVER_PORT'] != '80' && !IS_HTTPS ? $_SERVER['SERVER_PORT'] : ''));
    define('BASE_URL', 'http'.(!IS_HTTPS ? '' : 's').'://'.$_SERVER['SERVER_NAME'].(!SITE_PORT ? '' : ':'.SITE_PORT).SITE_PATH); // AutoGenerated to make links to website home
    define('BASE_PATH', realpath(__DIR__.'/../../'));
    define('ADMIN_EMAIL', ''); // Admin email -not used for now, can be used for error reporting and/or contact-

    // Utilities
    define('REGEXP_EMAIL', '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i'); // Regexp used for email validation
