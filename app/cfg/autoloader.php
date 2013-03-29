<?php

    function MuPHP_autoload()
    {
        $libFolders = scandir(__DIR__.'/../lib/');
        foreach ($libFolders as $folder)
        {
            if ($folder == '.' || $folder == '..' || $folder == 'tests' || $folder == '.svn') continue;
            $libFiles = scandir(__DIR__."/../lib/{$folder}");
            foreach ($libFiles as $file)
            {
                if ($file == '.' || $file == '..' || strpos($file, '.inc') !== false) continue;
                include_once __DIR__."/../lib/{$folder}/{$file}";
            }
        }
    }

    class MuPHPAutoloaderClassNotFoundException extends Exception
    {
        public function __construct()
        {
            $this->message = 'The demanded class could not be found! Exiting.';
        }
    }

    function MuPHP_spl_autoloader($name)
    {
        $arch = explode('\\', $name);
        $fileName = __DIR__.'/../lib/';
        for ($i = 0, $l = count($arch); $i < $l; ++$i)
        {
            if ($arch[$i] == 'MuPHP') continue;
            $fileName .= ($i === 1 ? strtolower($arch[$i]) : $arch[$i]).($i === $l - 1 ? '.php' : '/');
        }
        if (file_exists($fileName))
            include_once $fileName;
        else
            throw new MuPHPAutoloaderClassNotFoundException;
    }
