<?php
    require_once __DIR__.'/main.php';

    $result = array('error' => false);

    if (!isset($_GET['ws']))
    {
        $result['error'] = true;
        $result['errorText'] = "Webservice is not supplied";
        die(json_encode($result));
    }

    try { $wsObj = \TakPHPLib\WebserviceServer\apWs::factory($_GET['ws']); }
    catch (\TakPHPLib\WebserviceServer\apWsWebserviceNotFoundException $e)
    {
        \TakPHPLib\WebserviceServer\apWs::quit($e); exit;
    }

    try { $wsObj->run(); }
    catch (\TakPHPLib\WebserviceServer\apWsBadModeSupplied $e)
    {
        \TakPHPLib\WebserviceServer\apWs::quit($e); exit;
    }
