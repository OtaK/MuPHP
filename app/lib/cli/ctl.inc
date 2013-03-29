<?php

    namespace MuPHP\MVC;
    class __CLASSNAME__ extends Module
    {
        public function create()
        {
            $querySet = '';
            foreach ($_POST as $key => $val)
            {
                $querySet .= \MuPHP\DB\DBMan::get_instance()->safeQuery(
                    '__TABLE_NAME___%s = \'%s\''.PHP_EOL,
                    array($key, $val)
                );
            }
            $result = \MuPHP\DB\DBMan::get_instance()->query('INSERT INTO __TABLE_NAME__s SET '.$querySet);
            $this->templateVar('result', $result);
        }

        public function update($id)
        {
            $querySet = '';
            foreach ($_POST as $key => $val)
            {
                $querySet .= \MuPHP\DB\DBMan::get_instance()->safeQuery(
                    '__TABLE_NAME___%s = \'%s\''.PHP_EOL,
                    array($key, $val)
                );
            }
            $result = \MuPHP\DB\DBMan::get_instance()->query('
                UDPATE __TABLE_NAME__s SET '.$querySet.' WHERE __TABLE_NAME___id = \'%d\'',
                array($id)
            );

            $this->templateVar('result', $result);
        }
        public function delete($id)
        {
            $result = \MuPHP\DB\DBMan::get_instance()->query('
                DELETE FROM __TABLE_NAME__s WHERE __TABLE_NAME___id = \'%d\'',
                array($id)
            );

            $this->templateVar('result', $result);
            return $result;
        }

        public function index()
        {
            $data = \MuPHP\DB\DBMan::get_instance()->query('
                SELECT *
                FROM __TABLE_NAME__s
                ORDER BY __TABLE_NAME___id ASC'
            );

            $this->templateVar('data', $data);
        }

        public function details($id)
        {
            $data = \MuPHP\DB\DBMan::get_instance()->singleResQuery("
                SELECT *
                FROM __TABLE_NAME__s
                WHERE __TABLE_NAME___id = '%d'",
                array($id)
            );

            $this->templateVar('data', $data);
        }

        protected function _compute()
        {
            if (isset($_GET['id']))
            {
                $id = $_GET['id'];
                if (isset($_GET['delete']) && $_GET['delete'] == '1')
                {
                    if ($this->delete($id))
                        header('Location:__LOWER_CLASSNAME__?delete_success=1');
                }
                if ($this->_posted)
                    $this->update($id);
                $this->callAction('details', array($id));
            }
            else
            {
                if ($this->_posted)
                    $this->create();
                $this->callAction('index');
            }
        }
    }