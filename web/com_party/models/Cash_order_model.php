<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @提现模块
 * @author wangchuan
 * @category 2015-5-13
 * @version
 */
class Cash_order_model extends Base_model{
    public $table;
	public $pk = 'id';
    public function __construct()
    {
        parent::__construct('cash_order');
    }
    //返回数组状态
    //如非有特殊需要，像此类的状态数组最好写成类属性的方式
    public $get_cash_order_all_status = array('fail'=>'失败','new'=>'新申请','pending'=>'审核中','cancelled'=>'已取消','passed'=>'转款中','done'=>'完成');
	/*public function get_cash_order_all_status(){
		return array('fail'=>'失败','new'=>'新申请','pending'=>'审核中','cancelled'=>'已取消','passed'=>'转款中','done'=>'完成');
	}*/
	//通过当前状态获取操作状态
	public function get_cash_switch_status($status){
		switch ($status) {
		case 'fail':
			return array('new'=>'新申请','fail'=>'失败');
			break;
		case 'new':
			return array('pending'=>'审核中','cancelled'=>'已取消');
			break;
		case 'pending':
			return array('pending'=>'审核中','passed'=>'完成','cancelled'=>'已取消');
			break;
		case 'passed':
			return array('done'=>'完成');
			break;
		default:
			return array();
		}
	}
	public function _where($where)
    {

        if (!empty($where['id'])) {
            $this->db->where("{$this->table}.id=", $where['id']);
        }
		if (!empty($where['order_no'])) {
            $this->db->where("{$this->table}.order_no=", $where['order_no']);
        }
		if (!empty($where['card_no'])) {
            $this->db->where("{$this->table}.card_no=", $where['card_no']);
        }
		if (!empty($where['uid'])) {
            $this->db->where("{$this->table}.uid=", $where['uid']);
        }
		if (!empty($where['status'])) {
            $this->db->where("{$this->table}.status=", $where['status']);
        }
        if (!empty($where['start_time'])) {
            $this->db->where("{$this->table}.created >=", $where['start_time']);
        }
        if (!empty($where['end_time'])) {
            $this->db->where("{$this->table}.created <=", $where['end_time']);
        }


        $this->db->order_by("{$this->table}.id desc,{$this->table}.created desc");
    }
	 public function sum($field, $where)
    {
        if(!$field){
            return NULL;
        }
        if($where){
            $this->_where($where);
        }
        foreach($field as $v){
            $this->db->select_sum($v);
        }
	    $query = $this->db->get($this->table);
        $result = $query->result_array();
		if($result){
			return $result[0];	
		}else{
			return array();	
		}
    }
	public function all_group_status($where = array(), $select = array())
    {
		$this->_where($where);
		$this->db->select( "COUNT(id) AS cs_nums, status");
		$this->db->group_by('status');
		$query = $this->db->get($this->table);
		return $query->result_array();
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
                $ids[] = $row['id'];
            }
        }
        return $ids;
    }



}
