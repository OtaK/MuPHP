<?php

    function MuPHP_autoload()
    {
        $libFolders = scandir(__DIR__.'/../lib/');
        foreach ($libFolders as $folder)
        {
            if ($folder == '.' || $folder == '..' || $folder == 'tests') continue;
            $libFiles = scandir(__DIR__."/../lib/{$folder}");
            foreach ($libFiles as $file)
            {
                if ($file == '.' || $file == '..') continue;
                include_once __DIR__."/../lib/{$folder}/{$file}";
            }
        }
    }