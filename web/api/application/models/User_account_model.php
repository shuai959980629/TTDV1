<?php
/**
 * 用户资金 MODEL
 *
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://mrg.tt1.com.cn)
 * @license	http://mrg.tt1.com.cn
 * @link	http://mrg.tt1.com.cn
 * @since	Version 3.0.0 2015-05-14
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User_account_model extends Base_Model
{
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct('user_account');
        $this->pk = 'uid';
    }

    /**
     * 获取用户资金单条数据
     *
     * @param  integer $uid	//用户UID
     * @access public
     * @return array
     */
    public function getRow($uid)
    {
    	$this->db->where('uid', $uid);
    	$result = $this->db->get($this->table);

    	return $result->row_array();
    }

    /**
     * 获取多条数据
     *
     * @param  array 	$where	//查询条件
     * @param  integer	$limit	//查询条数
     * @param  integer 	$offset	//偏移量
     * @access public
     * @return array
     */
    public function getSearch($where = array(), $limit = 20, $offset = 0)
    {
    	$orderBy = "{$this->pk} DESC";
    	
		if (isset($where['order']) && isset($where['by'])) {
			if (strpos($where['order'], ',')) {
				$order = explode(',', $where['order']);
				$by    = explode(',', $where['by']);
				$orderBy = array();
				foreach ($order as $key=>$value) {
					!empty($value) && $orderBy[] = $value . ' ' . (isset($by[$key]) ? $by[$key] : 'DESC');
				}
				$orderBy = implode($orderBy, ',');
			} elseif (!strpos($where['order'], ',') && !strpos($where['by'], ',')) {
				$orderBy = "{$where['order']} {$where['by']}";
			}
		}
		$this->db->order_by($orderBy);
		
		unset($where['order']);
		unset($where['by']);
		
    	$this->db->limit($limit, $offset);
        if (!empty($where['in'])) {
        	$this->db->where_in($where['in'][0], $where['in'][1]);
	        unset($where['in']);
        }
        !empty($where) && $this->db->where($where);
        $query = $this->db->get($this->table);

    	return $query->result_array();
    }

    /**
     * 获取总数
     *
     * @param  array $where	//查询条件数组
     * @access public
     * @return number
     */
    public function getTotal($where = array())
    {
    	if (!empty($where['in'])) {
        	$this->db->where_in($where['in'][0], $where['in'][1]);
	        unset($where['in']);
        }
        !empty($where) && $this->db->where($where);

        return $this->db->count_all_results($this->table);
    }

    /**
     * 创建用户资金总量表数据
     *
     * @param  array $data	//用户数据
     * @return boolean
     */
    public function createUserAccount($data)
    {
        if ($data['uid'] <= 0) {
            return false;
        }

        $result = $this->getRow($data['uid']);
    	if (!empty($result)) {
    		return $this->modify($data['uid'], $data);
    	}

        $this->db->insert($this->table, $data);

        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改用户资金数据
     *
     * @param  integer $uid	//用户UID
     * @param  array   $data //修改数据
     * @access public
     * @return boolean
     */
    public function modify($uid, $data)
    {
    	return $uid;
    	
//    	if (empty($uid)) {
//    		return false;
//        }
//
//        unset($data['uid']);
//
//    	$where = array('uid' => $uid);
//    	$this->db->where($where)->update($this->table, $data);
//
//        if ($this->db->affected_rows() > 0) {
//            return true;
//        } else {
//            return false;
//        }
    }
    
    /**
     * 根据参数获取查询统计数据
     * 
     * @param  array   $fields //查询条件
     * @access public
     * @copyright 20150817
     * @return array
     */
    public function getCount($fields)
    {
    	if (!empty($fields['sum'])) {
    		$where['sum'] = $fields['sum'];
    	}
    	
    	if (!empty($fields['cols'])) {
    		$where['cols'] = $fields['cols'];
    	}
    	
    	if (!empty($fields['not_in'])) {
    		$not_in = explode(',', $fields['not_in']);
    		$field  = array_shift($not_in);
    		$where['not_in'] = array($field => $not_in);
    	}
    	
    	$this->dbWhere($where);
    	$query = $this->db->get($this->table);
    	
    	return $query->row_array();
    }
}
?>