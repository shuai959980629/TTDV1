<?php
/**
 * 资金统计接口 FUNDS_COUNT CONTROLLER
 *
 * @package		CONTROLLER
 * @author		LEE
 * @copyright	Copyright (c) 2015 (http://api.tutengdai.com)
 * @license		图腾贷
 * @link		http://api.tutengdai.com
 * @since		Version 1.0.0 2015-08-17
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Funds_count extends CI_Controller
{
	/**
	 * 请求方式
	 *
	 * @var string
	 */
	private $mod = 'GET';
	
	/**
	 * 日志文件名
	 *
	 * @var string
	 */
	private $filename = '';
	
	/**
	 * Class constructor
	 *
	 * @return	void
	 */
    public function __construct()
    {
        parent::__construct();
        //获取调用方法
        $segment = $this->uri->segment(2, 0);
        
        $this->load->model('user_account_model', 'user_account');
        $this->load->model('account_log_model' , 'account_log');
        $this->load->model('cash_free_model'   , 'cash_free');
        
        $URL = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        $_SERVER['REQUEST_METHOD'] == 'POST' && $this->mod = 'POST';
        if ($this->mod == 'POST') {
        	$param = array();
        	foreach ($_POST as $key=>$value) {
        		if ($key == 'uid') {
        			$uid = explode(',', $value);
        			$param[] = 'sum(uid)=' . (!empty($uid) ? count($uid) : 0);
        		} else {
        			$param[] = $key . '=' . $value;
        		}
        	}
        	$URL .= '?' . implode($param, '&');
        }
        
        $this->filename = 'api-' . $segment . "-" . strtolower($this->mod);
        
        log_message('error', $URL, $this->filename);
        
        header("Content-type: application/json;charset=utf-8");
    }
    
    /**
     * 获取指定用户资金记录信息
     * 调用方法 [get : http://test.api.tt1.com.cn/funds_count/getAccCount/?sum=balance&not_in=uid,1,2,3]
     * 
     * @access public
     * @return json
     */
    public function getAccCount()
    {
        $fields = $this->input->get();
        
    	$result = $this->user_account->getCount($fields);

    	$error = array(
    		'error' => 1,
    		'msg'	=> '执行成功',
    		'data'	=> $result
    	);
    	
    	log_message('error', urldecode(http_build_query($error)), $this->filename);
    	
    	echo json_encode($error);
    	return;
    }
    
    /**
     * 获取用户可免手续费金额
     * 调用方法 [get : http://test.api.tt1.com.cn/funds_count/getCashFree/?uid=16260]
     * 
     * @access public
     * @return json
     */
    public function getCashFree()
    {
    	$fields   = $this->input->get();
    	$cashFree = $this->cash_free->getCashFree($fields);
    	
    	$result = array('cash_free' => $cashFree);
    	
    	$error = array(
    		'error' => 1,
    		'msg'	=> '执行成功',
    		'data'	=> $result
    	);
    	
    	log_message('error', urldecode(http_build_query($error)), $this->filename);
    	
    	echo json_encode($error);
    	return;
    }
}