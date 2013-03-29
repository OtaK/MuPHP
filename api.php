<?php
    require_once __DIR__ . '/app/lib/webserviceserver/APWS.php';

    $result = array('error' => false);

    if (!isset($_GET['ws']))
    {
        $result['error'] = true;
        $result['errorText'] = "Webservice is not supplied";
        die(json_encode($result));
    }

    try { $wsObj = \MuPHP\WebserviceServer\APWS::factory($_GET['ws']); }
    catch (\MuPHP\WebserviceServer\APWSWebserviceNotFoundException $e)
    {
        \MuPHP\WebserviceServer\APWS::quit($e); exit;
    }

    try { $wsObj->run(); }
    catch (\MuPHP\WebserviceServer\APWSBadModeSupplied $e)
    {
        \MuPHP\WebserviceServer\APWS::quit($e); exit;
    }
