<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @提现操作记录模块
 * @author wangchuan
 * @category 2015-5-14
 * @version 
 */
class Cash_log_model extends Base_model{
	
     public function __construct()
    {
        parent::__construct('cash_log');
    }

	    
    public function write($data)
    {
        if (!isset($data['id']) || empty($data['id'])) {
            $data['id'] = unique_id();
        }
        return parent::create($data);
    }
	public function get_cash_logs($data, $pot = '')
    {
        //$this->switching('cash_log', $pot);
		$this->db->order_by("{$this->table}.id asc, {$this->table}.created asc");
        return parent::all($data);
    }
}
