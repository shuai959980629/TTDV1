<?php
/**
 * 银行配置 MODEL
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

class Cfg_bank_model extends Base_model 
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
        parent::__construct('cfg_bank');
    }
    
    /**
     * 获取全部可用银行
     *
     * @access public
     * @return array
     */
    public function getAll()
    {
    	$this->db->order_by('sort_order', 'DESC');
    	$query = $this->db->get_where($this->table, array('enable' => 1));

        return $query->result_array();
    }
}
?>