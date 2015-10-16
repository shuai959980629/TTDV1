<?php
/**
 * 统计每天充值金额并短信通知
 * 
 * @package	CONTROLLER
 * @author	zxj
 * @copyright	Copyright (c) 2015 tt1_p2p (http://cli.tt1.com.cn)
 * @license	http://cli.tt1.com.cn
 * @link	http://cli.tt1.com.cn
 * @since	Version 2015-09-24
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Recharge_total extends MY_Controller 
{
    public function __construct()
    {
    	header("Content-type:charset=utf-8");   	
        parent::__construct();      
        $this->output->enable_profiler(false);
    }
    /**
     * 发送短信
     *
     * @access public
     * @return boolean
     */
    public function index()
    {
		$this->load->model('Recharge_model','recharge');
		$this->recharge->sendRechargeTotal();
		return true;
    }
}