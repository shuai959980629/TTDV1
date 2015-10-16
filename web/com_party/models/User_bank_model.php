<?php
/**
 * 银行账号 MODEL
 * 
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://mrg.tt1.com.cn)
 * @license	http://mrg.tt1.com.cn
 * @link	http://mrg.tt1.com.cn
 * @since	Version 3.0.0 2015-05-12
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User_bank_model extends Base_model 
{
	/**
	 * 主键ID
	 *
	 * @var string
	 */
	public     $pk    = 'id';
	
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct('user_bank');
    }
    
    /**
     * 获取总数
     *
     * @param  array $where	//查询条件数组
     * @access public
     * @return number
     */
    public function getTotal($where)
    {
    	$this->db->where_in($where['key'], $where['val']);
        return $this->db->count_all_results($this->table);
    }
    
    /**
     * 分页查询
     *
     * @param  array 	$where	//查询条件
     * @param  integer	$limit	//查询条数
     * @param  integer 	$offset	//偏移量
     * @access public
     * @return array
     */
    public function search($where = array(), $limit = 20, $offset = 0, $select = array())
    {
    	$this->db->order_by("{$this->table}.{$this->pk} desc");
    	$this->db->limit($limit, $offset);
        $this->db->where_in($where['key'], $where['val']);
        $query = $this->db->get($this->table);

        return $query->result_array();
    }
    
    /**
     * 修改数据
     *
     * @param  array $data	//数据
     * @param  array $where	//条件
     * @access public
     * @return boolean
     */
    public function modify($data, $where)
    {
    	$this->db->where($where);
    	
		return $this->db->update($this->table, $data) ? true : false;
    }
    
    /**
     * 删除数据
     *
     * @param  integer $id	//自增ID
     * @access public
     * @return boolean
     */
    public function del($id)
    {
    	return $this->db->delete($this->table, array('id' => $id)); 
    }
    
    /**
     * 获取单条数据
     *
     * @param  integer $id
     * @access public
     * @return array
     */
    public function getRow($id)
    {
    	$this->db->where('id', $id);
    	$result = $this->db->get($this->table);
    	
    	return $result->row_array();
    }
    
    /**
     * 获取数据
     *
     * @return boolean
     */
	 public function search_bank($where = array(), $limit = 20, $offset = 0, $select = array())
    {
        if ($offset > 0) {
            $ids = $this->_find_ids($where, $limit, $offset);
            if(!$ids){
                return NULL;
            }
            $this->db->where_in($this->pk, $ids);
            if (isset($where['order_by']) && !empty($where['order_by'])) {
                $this->db->order_by($where['order_by']);
            }
            else {
                $this->db->order_by("{$this->pk} desc");
            }
        }
        else {
            $this->db->limit($limit);
            $this->_where($where);
        }

        if($select){
            $this->_select($select);
        }

        $query = $this->db->get($this->table);

        return $query->result_array();
    } 
}
?>