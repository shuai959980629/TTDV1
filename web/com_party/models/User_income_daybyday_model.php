<?php
/**
 * 用户每日收入记录[暂定7日] MODEL
 * 
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://www.tt1.com.cn)
 * @license	http://www.tt1.com.cn
 * @link	http://www.tt1.com.cn
 * @since	Version 3.0.0 2015-06-08
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User_income_daybyday_model extends Base_model 
{
	/**
	 * 主键ID
	 *
	 * @var string
	 */
	public     $pk    = 'uid';
	
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct('user_income_daybyday');
    }
    
    /**
     * 获取单条数据
     *
     * @param  array 	$where	//查询条件 array('uid' => 1, 'dateTime' => time())
     * @access public
     * @return array
     */
    public function getRow($where)
    {
    	$this->db->where($where);
    	$result = $this->db->get($this->table);
    	
    	return $result->row_array();
    }
    
    /**
     * 查询多条
     *
     * @param  array 	$where	//查询条件 array('uid' => 1, 'dateTime' => time())
     * @access public
     * @return array
     */
    public function getRows($where)
    {
    	if (!empty($where['limit'])) {
    		$this->db->limit($where['limit']);
    		unset($where['limit']);
    	}
    	$this->db->where($where);
    	$this->db->order_by("{$this->table}.dateTime desc");
        $query = $this->db->get($this->table);

        return $query->result_array();
    }
    
    /**
     * 删除数据
     *
     * @param  array 	$where	//删除条件 array('uid' => 1, 'dateTime' => time())
     * @access public
     * @return boolean
     */
    public function del($where)
    {
    	return $this->db->delete($this->table, $where); 
    }
    
    /**
     * 插入一条数据
     *
     * @param  array 	$data	//插入数据
     * @access public
     * @return boolean
     */
    public function createRow($data)
    {
		$this->db->insert($this->table, $data);
		return $this->db->affected_rows() > 0 ? true : false;
    }
}
?>