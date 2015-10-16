<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @用户邀请模块
 * @author wangchuan
 * @category 2015-6-25
 * @version 
 */
class User_invite_model extends Base_model{
	public $table;
	public $pk = 'id';
    public function __construct()
    {
        parent::__construct();
        $this->table = 'user_invite';
    }
	public function _where($where){
		if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
            unset($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->table}.{$this->pk} desc");
        }
		
		if (!empty($where['uid'])) {
            $this->db->where("{$this->table}.uid=", $where['uid']);
        }
		if (!empty($where['invited_uid'])) {
            $this->db->where("{$this->table}.invited_uid=", $where['invited_uid']);
        }
        if (!empty($where['start_time'])) {
            $this->db->where("{$this->table}.created >=", strtotime($where['start_time']));
        }
        if (!empty($where['end_time'])) {
            $this->db->where("{$this->table}.created <=", strtotime($where['end_time'])+86400);
        }
	}		

}
