<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sharding_model extends Base_Model {

    private    $_idx_table = NULL;

    public function __construct($table = NULL)
    {
        parent::__construct($table);

        if (!empty($table)) {
            $this->_idx_table = $table;
            /*$this->table      = $this->get_table($this->_idx_table);*/
        }
    }

    protected function get_table($table, $pot = '')
    {
        $_map   = $this->config->item('table_map');
        $_table = $table;

        if (isset($_map[$table])) {
            $_table = NULL;
            $pot = empty($pot) ? date('Ymd') : preg_replace('/[-\/\s]+/', '', $pot);
            foreach ($_map[$table] as $d => $t) {
                $_d = explode('-', $d);
                if ($pot >= $_d[0] && $pot <= $_d[1]) {
                    $_table = $t;
                    break;
                }
            }
            if (empty($_table)) {
                log_message('error', "无法获取分表名，请检查application/config/sharding.php，{$_table}，{$table}，{$pot}");
//                show_error('无法获取分表名，与管理员系统', 500);
                return false;
            }
        }

        return $_table;
    }

    public function save($data, $id = 0, $pot = '')
    {
        if ((int) $id > 0) {
            return $this->update($id, $data, $pot);
        }
        else {
            return $this->create($data);
        }
    }

    public function create($data)
    {
        $this->switching($this->_idx_table);
        return parent::create($data);
    }

    public function remove($id, $pot = '')
    {
        $this->switching($this->_idx_table, $pot);
        return parent::remove($id);
    }

    public function update($id, $data, $pot = '')
    {
        $this->switching($this->_idx_table, $pot);
        return parent::update($id, $data);
    }

    public function location($pot)
    {
        $pot    = preg_replace('/[-\/\s]+/', '', $pot);
        $_map   = $this->config->item('table_map');
        $_pot   = NULL;

        if (isset($_map[$this->_idx_table])) {
            foreach ($_map[$this->_idx_table] as $d => $t) {
                $_d = explode('-', $d);
                if ($pot >= $_d[0] && $pot <= $_d[1]) {
                    $_pot = $_d;
                    break;
                }
            }
        }

        return $_pot;
    }

    public function switching($table, $pot = '')
    {
        $this->table = $this->get_table($table, $pot);
    }
}



