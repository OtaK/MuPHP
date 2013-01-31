<?php

    function MuPHP_autoload()
    {
        $libFolders = scandir(__DIR__.'/../lib/');
        foreach ($libFolders as $folder)
        {
            if ($folder == '.' || $folder == '..' || $folder == 'tests' || $folder == '.svn') continue;
            $libFiles = scandir(__DIR__.'/../lib/'.$folder);
            foreach ($libFiles as $file)
            {
                if ($file == '.' || $file == '..' || $file == 'api.php' || $file == 'includes') continue;
                include_once __DIR__.'/../lib/'.$folder.'/'.$file;
            }
        }
    }