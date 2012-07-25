<?php

    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
        die(json_encode(null, JSON_FORCE_OBJECT));

    define('AUTOLOAD', false);
    include_once 'define.php';
    $i18n = new \TakPHPLib\Locales\localeLoader($shortLocale);
    $i18n->selectSection(\TakPHPLib\Locales\localeLoader::LOCALE_DIALOGS);

    $config = array(
        'DEBUG' => DEBUG,
        'sitePath' => SITE_PATH,
        'currentLocale' => CURRENT_LOCALE,
        'baseUrl' => BASE_URL,
        'FB' => array(
            'appId' => FB_APPID,
            'appUrl' => FB_APP_URL
        ),
        'locale' => $i18n->buildPageArray(),
    );

    die(json_encode($config, JSON_FORCE_OBJECT));
