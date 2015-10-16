<?php
/**
 * CLI REPAY MODEL
 *
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://cli.tt1.com.cn)
 * @license	http://cli.tt1.com.cn
 * @link	http://cli.tt1.com.cn
 * @since	Version 3.0.0 2015-07-15
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Cli_repay_model extends Sharding_Model
{
	/**
	 * 执行数据存放路径
	 * 
	 * @return integer
	 */
	private $filePath = 'data/monitor/';
	
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct('repay_log');
        
        $this->load->model('repay_log_model' , 'repay_log');
    }
    
    /**
     * 获取返本标信息
     *
     * @access public
     * @return array
     */
    public function repay()
    {
    	$this->load->model('cli_borrow_model', 'cli_borrow');
    	
    	$fileName = 'repay.' . date('Y-m-d') . ".txt";
    	$result   = array();
		$again    = $this->uri->segment(4, 0);
		
		//判断是否为监控接口调用
		if (!empty($again) && $again == 'again') {
			//数据监控用
			$json 	= file(FCPATH . $this->filePath . $fileName);
    		$borrow = !empty($json[0]) ? json_decode($json[0], true) : '';
		} else {
			//正常还本、还息操作读取数据
			$borrow = $this->cli_borrow->getBorrow();
		}
    	
    	if (!empty($borrow)) {
    		$result = $borrow;
    		//先存入数据
    		$this->jsonEncode($result, $fileName);
    		
			foreach ($borrow as $value) {
				//执行成功后 在剔除成功数据 并把未执行数据重新存入
    			if ($this->repay_log->repay_by_borrow($value) === TRUE) {
    				$json = file(FCPATH . $this->filePath . $fileName);
    				if (!empty($json) && !empty($json[0])) {
    					$json = json_decode($json[0], true);
    					//剔除
    					unset($result[$value]);
    					//重新存入
    					$this->jsonEncode($result, $fileName);
    				}
    			}
    		}
    	}
    	
    	return true;
    }
    
    /**
     * 生成标ID日志文件 JSON
     *
     * @param  array  $data		//标ID数组
     * @param  string $fileName	//文件名
     * @return void
     */
    private function jsonEncode($data, $fileName)
    {
    	$json = json_encode($data);
		writeFile($json, $fileName, FCPATH . $this->filePath);
    }
}