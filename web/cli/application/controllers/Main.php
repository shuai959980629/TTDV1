<?php
/**
 * CLI任务集合
 * 
 * @package	CONTROLLER
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://cli.tt1.com.cn)
 * @license	http://cli.tt1.com.cn
 * @link	http://cli.tt1.com.cn
 * @since	Version 2015-06-24
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends MY_Controller 
{
	/**
	 * 时间戳
	 * 
	 * @return integer
	 */
	private $time = 0;
	
	/**
	 * 执行开始时间
	 * 
	 * @return integer
	 */
	private $startTime = 0;
	
	/**
	 * Class constructor
	 *
	 * @return	void
	 */
    public function __construct()
    {
    	header("Content-type:charset=utf-8");
    	
        parent::__construct();
        
        $this->output->enable_profiler(false);
        
        $this->startTime = array_sum(explode(' ', microtime()));
    }
    
    /**
     * 调用入口
     *
     * @access public
     * @return boolean
     */
    public function index()
    {
    	$true = false;
    	$mod  = $this->uri->segment(3, 0);
    	$fun  = $this->uri->segment(4, 0);
    	
    	$startTime = date('Y-m-d H:i:s', $this->startTime);
    	
    	if (!empty($mod) && method_exists($this, $mod)) {
    		$true = $this->{$mod}();
    	}
    	
    	$micotime = number_format((array_sum(explode(' ', microtime())) - $this->startTime), 6);
    	
    	$info = array(
    		'startTime=' . $startTime,
    		'endTime=' . date('Y-m-d H:i:s'),
    		'mod=' . (!empty($mod) ? $mod : 'null'),
    		'fun=' . (!empty($fun) ? $fun : 'null'),
    		'micotime=' . $micotime
    	);
    	$info = implode($info, '&');
    	
    	//记录日志
    	log_message('error', $info, 'task');
    	
    	return $true;
    }
    
    /**
     * 统计用户金额数据任务 user_account_count表数据
     *
     * @access private
     * @return boolean
     */
    private function uacount()
    {
    	$this->load->model('user_account_count_model', 'user_account_count');
    	$this->user_account_count->saveCount();
    	return true;
    }
    
    /**
     * 土豪榜任务
     *
     * @access private
     * @return boolean
     */
    private function tuhao()
    {
    	$this->load->model('user_tender_list_model', 'user_tender_list');
    	$this->user_tender_list->checkData();
    	return true;
    }
    
    /**
     * 标任务
     *
     * @access private
     * @return boolean
     */
    private function borrow()
    {
    	$true = false;
    	$fun  = $this->uri->segment(4, 0);
    	
    	if (!empty($fun) && method_exists($this, $fun)) {
    		$this->load->model('borrow_model', 'borrow');
    		$true = $this->{$fun}();
    	}
    	
    	return $true;
    }
    
    
    /**
     * 待审标、已发布但是不可投标
     *
     * @access private
     * @return boolean
     */
    private function pending()
    {
    	$where = array(
    		'in' => array('status' => array('pending', 'published')),	//待审、已发布但是不可投
    	);
    	$borrow = $this->borrow->getRows($where);
    	
    	if (!empty($borrow)) {
    		$this->time = time();
    		foreach ($borrow as $value) {
    			$this->setAuto($value);
    		}
    	}
    	
    	return true;
    }
    
    /**
     * 自动审核 自动投标 [5分钟内]
     *
     * @param  array $borrow	//标数据
     * @return boolean
     */
    private function setAuto($borrow)
    {
    	//自动审核、自动投标 
    	if (!empty($borrow['auto_time']) && $borrow['auto_time'] <= $this->time
			&& ($this->time - $borrow['auto_time']) <= 300) {
				if ($borrow['is_auto'] == 1) {
					//自动投标
					$this->borrow->auto_invest($borrow);
				} else {
					//初审
					$this->borrow->verify($borrow['id'], 'verified');
				}
		}
		
		return true;
    }
    
    /**
     * 满标审核
     *
     * @access private
     * @return boolean
     */
    private function fills()
    {
    	$where = array(
    		'status' => 'fills',	//满标审核
    	);
    	$borrow = $this->borrow->getRows($where);
    	
    	if (!empty($borrow)) {
    		foreach ($borrow as $value) {
    			if ($value['uid'] > 0) {
					//债权标 满标审核
					$this->borrow->transfer_fills($value);
				} else {
					//满标审核
					$this->borrow->fills($value);
				}
    		}
    	}
    	
		return true;
    }
    
    /**
     * 流标 [债权转让]
     *
     * @access private
     * @return void
     */
    private function canceled()
    {
    	$this->load->model('cli_borrow_model', 'cli_borrow');
    	$this->load->model('borrow_model', 'borrow');
    	
    	$where = array(
    		'cols'	 => array('id', 'uid', 'title', 'verified_time', 'tender_id', 'amount'),
    		'status' => 'verified',	//发标初审
    		'scope'  => array(
    			'uid' => array('ltt' => 1)
    		)
    	);
    	$borrow = $this->borrow->getRows($where);
    	
    	if (!empty($borrow)) {
    		foreach ($borrow as $value) {
    			$this->cli_borrow->canceled($value);
    		}
    	}
    	
    	return true;
    }
    
    /**
     * 还本 还息
     *
     * @access private
     * @return boolean
     */
    private function repay()
    {
    	$this->load->model('cli_repay_model', 'cli_repay');
    	
    	return $this->cli_repay->repay();
    }
    
    /**
     * 发送短信
     * 
     * @access private
     * @return boolean
     */
    private function sms()
    {
    	$this->load->model('repay_log_model', 'repay_log');
    	
        return $this->repay_log->sms_repay();
    }

    /**
     * 检测是否用户达到VIP要求（总资产大于五万）并且还不是VIP自动添加VIP
     *
     * @access private
     * @return boolean
     */
    private function vip()
    {
        $this->load->model('User_bag_model', 'user_bag');

        return $this->user_bag->vip_job();
    }

}