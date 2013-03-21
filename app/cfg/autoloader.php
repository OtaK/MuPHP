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

  /*  function MuPHP_spl_autoloader($name)
    {
        $arch = explode('\\', $name);
        $fileName = __DIR__.'/../lib/';
        for ($i = 0, $l = count($arch); $i < $l; ++$i)
        {
            $fileName .=
        }
        if (file_exists())
    }*/
