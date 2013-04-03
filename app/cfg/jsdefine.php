<?php

    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
        die(json_encode(null, JSON_FORCE_OBJECT));

    include_once 'define.php';
    $i18n = new \MuPHP\i18n\LocaleLoader($shortLocale);
    $i18n->selectSection(\MuPHP\i18n\LocaleLoader::LOCALE_DIALOGS);

    $config = array(
        'DEBUG' => DEBUG,
        'sitePath' => SITE_PATH,
        'currentLocale' => CURRENT_LOCALE,
        'baseUrl' => BASE_URL,
        'locale' => $i18n->buildPageArray(),
        'FB' => array(
            'appId' => FB_APP_ID,
            'appPermissions' => FB_APP_SCOPES
        )
    );

    die(json_encode($config, JSON_FORCE_OBJECT));
