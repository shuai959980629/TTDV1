<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_log_model extends Sharding_model {

    public function __construct()
    {
        parent::__construct('admin_log');
    }

    public function _where($where)
    {
        if (isset($where['uid']) && !empty($where['uid'])) {
            $this->db->where('uid', intval($where['uid']));
        }

        if (isset($where['model']) && !empty($where['model'])) {
            $this->db->where('model', $where['model']);
        }

        if (isset($where['start_time']) && !empty($where['start_time'])) {
            $this->db->where('created >=', $where['start_time']);
        }

        if (isset($where['end_time']) && !empty($where['end_time'])) {
            $this->db->where('created <=', "{$where['end_time']} 23:59:59");
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
            unset($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }

        $this->switching('admin_log', $where['end_time']);
    }

    public function write($data)
    {
        if (!isset($data['id']) || empty($data['id'])) {
            $data['id'] = unique_id();
        }
        return parent::create($data);
    }

    public function clear($uid = 0)
    {
        foreach ($this->_map_cfg['table_map'] as $k => $v) {
            if ($uid > 0) {
                $this->db->where('uid', $uid)
                    ->delete($v);
            }
            else {
                $this->db->truncate($v);
            }
        }
    }
}
