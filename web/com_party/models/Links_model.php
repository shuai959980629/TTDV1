<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Links_model extends Base_model {

    public function __construct()
    {
        parent::__construct('links');
    }

    public function remove($id)
    {
        $rows = $this->db->where_in('id', $id)
                         ->get($this->table)
                         ->result_array();

        if (empty($rows)) {
            return FALSE;
        }

        foreach ($rows as $row) {
            if (!empty($row['logo'])) {
                $link_file = APPPATH . "../data/{$row['logo']}";
                file_exists($link_file) && unlink($link_file);
            }
        }

        return parent::remove($id);
    }

    public function _where($where = array())
    {
        $where['order_by'] = "sort_order desc";
        parent::_where($where);
    }
}
