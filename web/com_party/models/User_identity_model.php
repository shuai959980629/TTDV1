<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @用户认证信息模块
 * @author wangchuan
 * @category 2015-5-12
 * @version 
 */
class User_identity_model extends Base_model{
     public $table;
	 public $pk = 'uid';
    public function __construct()
    {
        parent::__construct();
        $this->table = 'user_identity';
    }
	
    
     public function _where($where)
    {

        if (!empty($where['uid'])) {
            $this->db->where("{$this->table}.uid=", $where['uid']);
        }
		if (!empty($where['realname'])) {
            $this->db->where("{$this->table}.realname=", $where['realname']);
        }
		if (!empty($where['status'])) {
            $this->db->where("{$this->table}.status=", $where['status']);
        }
        if (!empty($where['start_time'])) {
            $this->db->where("{$this->table}.created >=", $where['start_time']);
        }
        if (!empty($where['end_time'])) {
            $this->db->where("{$this->table}.created <=", "{$where['end_time']} 23:59:59");
        }
        $this->db->order_by("{$this->table}.created desc,{$this->table}.uid desc");
    }
	 public function _find_ids($where, $limit, $offset)
    {
        $this->db->select("{$this->table}.{$this->pk}");
        $this->_where($where);
        $this->db->limit($limit, $offset);

        $query = $this->db->get($this->table);

        $ids = array();

        if ($query->num_rows() > 0) {
            $rows = $query->result_array();
            foreach ($rows as $row) {
                $ids[] = $row['uid'];
            }
        }

        return $ids;
    }
    /**
     * 获取指定uid的用户实名信息
     * @param  integer $uid 用户id
     * @author zhang xiaojian
     * @return array        返回实名认证信息
     */
    public function getIdentityByUid($uid=0)
    {
        if ($uid==0) {
            return null;
        }
        $this->_where(array('uid'=>$uid));
        $query = $this->db->get($this->table);
        return $query->first_row('array');
    }
	
	//通过uid获取用户实名认证数组
	public function get_realname_by_uidarr($data=array()){
		$this->db->select('uid,realname,status');
		$this->db->where_in("{$this->table}.uid", $data);
		$query = $this->db->get($this->table);
        if ($query->num_rows() > 0) {
			//返回一维数组
			$returns = $query->result_array();
			$redata = '';
			foreach($returns as $k => $v){
				$redata[$v['uid']]['realname']=$v['realname'];
				$redata[$v['uid']]['status']=$v['status'];
			}
            return $redata;
        }
        else {
            return array();
        }
	}
	
	
}
