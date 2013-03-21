<?php

    namespace MuPHP\CLI;


    class Console
    {
        public function run($args)
        {
            if (method_exists($this, $args[1]))
                call_user_func_array(array($this, $args[1]), array_slice($args, 2));
        }

        public function generate()
        {
            $args = func_get_args();
            $type = $args[0];
            switch ($type)
            {
                case 'module':
                    $this->_generateModule($args[1]);
                    break;
                case 'model':
                    $this->_generateModel($args[1]);
                    break;
                default:
                    echo 'Not supported yet'.PHP_EOL;
                    break;
            }

        }

        private function _generateModule($moduleName)
        {
            $appPath         = __DIR__ . '/../../';
            $ctlContents     = file_get_contents(__DIR__ . '/ctl.inc');
            $detailsContents = file_get_contents(__DIR__ . '/tpl_index.inc');
            $indexContents   = file_get_contents(__DIR__ . '/tpl_details.inc');

            // Make templates directory
            mkdir($appPath . '/_tpl/' . $moduleName);

            $replacements    = array(
                '__CLASSNAME__'       => ucfirst($moduleName),
                '__LOWER_CLASSNAME__' => strtolower($moduleName),
                '__TABLE_NAME__'      => strtolower(substr($moduleName, 0, -1))
            );
            $ctlContents     = str_replace(array_keys($replacements), $replacements, $ctlContents);
            $detailsContents = str_replace(array_keys($replacements), $replacements, $detailsContents);
            $indexContents   = str_replace(array_keys($replacements), $replacements, $indexContents);
            file_put_contents($appPath . '_ctl/' . $moduleName . '.php', $ctlContents);
            file_put_contents($appPath . '/_tpl/' . $moduleName . '/index.phtml', $indexContents);
            file_put_contents($appPath . '/_tpl/' . $moduleName . '/details.phtml', $detailsContents);

            echo 'Generation done.' . PHP_EOL;
        }

        private function _generateModel($modelName)
        {
            echo 'Not implemented yet, sorry. :-('.PHP_EOL;
        }

        static public function boot($args)
        {
            // We expect the user to have written stuff like generate module module_name, so 4 args counting filename
            $usage = "Usage: {$_SERVER["argv"][0]} generate [module|model]".PHP_EOL.PHP_EOL;
            if (count($args) < 4)
                die($usage);

            $console = new Console();
            $console->run($args);
        }
    }
