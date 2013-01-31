<?php
    require_once __DIR__ . '/app/lib/webservices/apWs.php';

    $result = array('error' => false);

    if (!isset($_GET['ws']))
    {
        $result['error'] = true;
        $result['errorText'] = "Webservice is not supplied";
        die(json_encode($result));
    }

    try { $wsObj = \MuPHP\WebserviceServer\apWs::factory($_GET['ws']); }
    catch (\MuPHP\WebserviceServer\apWsWebserviceNotFoundException $e)
    {
        \MuPHP\WebserviceServer\apWs::quit($e); exit;
    }

    try { $wsObj->run(); }
    catch (\MuPHP\WebserviceServer\apWsBadModeSupplied $e)
    {
        \MuPHP\WebserviceServer\apWs::quit($e); exit;
    }
