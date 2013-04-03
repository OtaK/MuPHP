<?php

    class MuPHPAutoloaderClassNotFoundException extends Exception
    {
        public function __construct($class)
        {
            $this->message = 'The demanded class '.$class.' could not be found! Exiting.';
        }
    }

    function MuPHP_spl_autoloader($name)
    {
        $arch = explode('\\', $name);
        $fileName = __DIR__.'/../lib/';
        for ($i = 0, $l = count($arch); $i < $l; ++$i)
        {
            if ($arch[$i] == 'MuPHP') continue;
            $fileName .= ($i >= 1 && $i < $l - 1 ? strtolower($arch[$i]) : $arch[$i]).($i === $l - 1 ? '.php' : '/');
        }
        if (file_exists($fileName)) // It's a lib!
            include_once $fileName;
        else // It's a meh!
            throw new MuPHPAutoloaderClassNotFoundException($name);
    }
