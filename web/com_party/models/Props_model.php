<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @道具包基础类
 * @author wangchuan
 * @category 2015-8-27
 * @version
 */
class Props_model extends Base_model {

    public function __construct($table = NULL)
    {
        parent::__construct();
        $this->table = 'user_bag';
		$this->config->load("props");
    }
	//道具条件查询
	public function _where($where)
    {
		//用户ID
        if (!empty($where['uid'])) {
            $this->db->where("{$this->table}.uid=", $where['uid']);
        }
		//通过道具编号查询
		if (!empty($where['props_id'])) {
            $this->db->where("{$this->table}.props_id=", $where['props_id']);
        }
		//通过关联ID查询
		if (!empty($where['ticket_id'])) {
            $this->db->where("{$this->table}.ticket_id=", $where['ticket_id']);
        }
		//通过业务类型查询
		if (!empty($where['tob'])) {
            $this->db->where("{$this->table}.tob=", $where['tob']);
        }
		//通过发放时间查询
        if (!empty($where['start_time'])) {
            $this->db->where("{$this->table}.created >=", strtotime($where['start_time']));
        }
        if (!empty($where['end_time'])) {
            $this->db->where("{$this->table}.created <=", strtotime($where['end_time'])+86400);
        }
        $this->db->order_by("{$this->table}.created desc");
    }
	
	//获取道具包配置
	public function get_bag_type(){
		$user_bag_type = $this->config->item('user_bag_type');
		$bag_type =array();
		foreach($user_bag_type as $k=>$v){
			$bag_type[$k]=$v["name"];	
		}
		return $bag_type;
	}
	//获取关联业务配置
	public function get_bag_tob(){
		$user_bag_type = $this->config->item('user_bag_type');
		$bag_type =array();
		foreach($user_bag_type as $k=>$v){
			$bag_type[$k]=$v["tob"];	
		}
		return $bag_type;
	}
	//发放道具
	public function send_props($data){
		$this->db->insert($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return isset($data[$this->pk]) ? $data[$this->pk] : $this->db->insert_id();
        }
        else {
            return NULL;
        }	
	}
	//使用道具
	public function use_props($id = 0){
		if ((int) $id > 0) {
			$result = $this->db->where($this->pk, (int) $id)
				->update($this->table, array("used"=>time()));
			if($result === FALSE){
				return FALSE;
			}
			if ($this->db->affected_rows() > 0) {
				return $id;
			}
			else {
				return NULL;
			}
		}else{
			return NULL;	
		}
	}
	


}



