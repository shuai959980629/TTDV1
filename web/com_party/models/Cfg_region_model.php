<?php
/**
 * 地区 MODEL
 * 
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://mrg.tt1.com.cn)
 * @license	http://mrg.tt1.com.cn
 * @link	http://mrg.tt1.com.cn
 * @since	Version 3.0.0 2015-05-13
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Cfg_region_model extends Base_model 
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
	 * @return	void
	 */
	public function __construct()
    {
        parent::__construct('cfg_region');
    }
    
    /**
     * 获取省份数据
     *
     * @access public
     * @return array
     */
    public function getProvince()
    {
    	$this->db->order_by('sort_order', 'DESC');
    	$query = $this->db->get_where($this->table, array('parent_id' => 1));

        return $query->result_array();
    }
    
    /**
     * 获取下级数据
     *
     * @access public
     * @return array
     */
    public function getRegion($pid)
    {
    	$this->db->order_by('sort_order', 'DESC');
    	$query = $this->db->get_where($this->table, array('parent_id' => $pid));

        return $query->result_array();
    }
}
?>