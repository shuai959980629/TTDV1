<?php
/**
 * CLI任务集合 监控器
 * 
 * 【调用方式】
 * 例：cli目录下   注：第三个参数为要监控任务的执行方法的字符串翻转 如：repay => yaper
 * 
 * //方式一：正常调用 [此方式默认去根目录下检查data文件下面的对应的数据文件是否存在，存在则重启任务，不存在则放弃]
 * # php -f index.php monitor/index/yaper
 * 
 * //方式二：监控的任务是不是执行，如未执行，需要传递执行下一任务 [nofile不检查数据文件，不填写则需要检查，存在数据则再次调用任务，不存在数据则调用之后的任务]
 * # php -f index.php monitor/index/yaper/[nofile/]main.index.uacount
 * 
 * //方式三：方式一的补充调用 [nofile不检查数据文件，并再次调用任务]
 * # php -f index.php monitor/index/oahut/nofile
 * 
 * @package	CONTROLLER
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://cli.tt1.com.cn)
 * @license	http://cli.tt1.com.cn
 * @link	http://cli.tt1.com.cn
 * @since	Version 2015-07-14
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Monitor extends CI_Controller 
{
	/**
	 * 需要监控的任务包含的字符
	 * 
	 * @return string
	 */
	private $str = '';
	
	/**
	 * 执行开始时间
	 * 
	 * @return integer
	 */
	private $startTime = 0;
	
	/**
	 * txt文件存放路径
	 * 
	 * @return integer
	 */
	private $txtPath = 'application/logs/';
	
	/**
	 * 执行数据存放路径
	 * 
	 * @return integer
	 */
	private $filePath = 'data/monitor/';
	
	/**
	 * Class constructor
	 *
	 * @return	void
	 */
    public function __construct()
    {
        parent::__construct();
        
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
    	$startTime = date('Y-m-d H:i:s', $this->startTime);
    	$this->str = $this->uri->segment(3, 0);
    	
    	if (!$this->str) return false;
    	
    	$this->str = strrev($this->str);
    	
    	$true = $this->run();
    	
    	$micotime = number_format((array_sum(explode(' ', microtime())) - $this->startTime), 6);
    	
    	$info = 'startTime=' . $startTime . '&endTime=' . date('Y-m-d H:i:s') . '&micotime=' . $micotime;
    	
    	//记录日志
    	log_message('error', $info, 'monitor.task.' . $this->str);
    	
    	return $true;
    }
    
    /**
     * 检查任务是否存在
     *
     * @access private
     * @return boolean
     */
    private function run()
    {
    	$ps  = 'ps -ef |grep main/index';
    	$txt = FCPATH . $this->txtPath . 'pooy.txt';
    	$this->systemShell("$ps > $txt");
		$arr 	= file($txt);
		$total 	= count($arr);
		$exist  = false;
		for($i = 0; $i < $total; $i++){
			if (stristr($arr[$i], $this->str) !== FALSE) {
				$exist = true;
				break;
			}
		}
		//被监控的任务是否执行完成
		if (!$exist) {
			
			$segment = $this->uri->segment(4, 0);
			
			/**
			 * 当第四位是nofile时
			 * 
			 * 1.存在第五位，直接在监控程序执行完成后执行第五位代码
			 * 2.不存在第五位，直接重复执行当前监控的程序
			 * 注：1、2都不检查是否存在文件数据
			 */
			if (!empty($segment) && $segment == 'nofile') {
				$next = $this->uri->segment(5, 0);
				return $next !== null ? $this->comExec($next) : $this->setExec();
			}
			/**
			 * 当第四位存在，不为nofile时
			 * 
			 * 1.文件数据存在，则重复执行当前监控的程序
			 * 2.文件数据不存在，则执行监控程序完成后需要执行的程序
			 * 注：1、2都要检查是否存在文件数据，只有当文件数据不存在才会调用之后需要执行的程序
			 * 也就是说，如果程序挂掉了，文件数据始终存在，那么永远不会执行之后用户传入的需要执行的程序
			 */
			elseif (!empty($segment)) {
				return $this->fileExist() ? $this->setExec() : $this->comExec($segment);
			}
			/**
			 * 当第四位不存在
			 * 
			 * 注：默认检查文件数据是否存在，不存在返回false，存在则重复执行当前监控的程序
			 */
			else {
				return $this->fileExist() ? $this->setExec() : false;
			}
		}
		
		return false;
    }
    
    /**
     * 组装需要再次执行的代码 后跟again表示再次执行
     *
     * @access private
     * @return boolean
     */
    private function setExec()
    {
    	//获取crontab信息
    	$crontab = $this->getCrontab();
		$_shell  = '';
		foreach ($crontab as $key=>$value) {
			if ($key > 4) {
				$_shell .= $value . ' ';
			}
		}
		$_shell = trim($_shell) . "/again";
		
		return $this->systemShell($_shell);
    }
    
    /**
     * 在监控程序执行完成后 组装用户传进来的需要执行代码
     *
     * @access private
     * @return boolean
     */
    private function comExec($segment)
    {
    	//获取crontab信息
    	$crontab = $this->getCrontab();
    	$com 	 = str_replace('.', '/', $segment);
    	$_shell  = '';
    	
    	if (empty($crontab)) {
    		return false;
    	}
    	
		foreach ($crontab as $key=>$value) {
			if ($key > 4) {
				$_shell .= $value . ' ';
			}
			if ($key >= 7) break;
		}
		$_shell = trim($_shell) . ' ' . $com;
		
		return $this->systemShell($_shell);
    }
    
    /**
     * 检查是否存在未执行完成的数据
     *
     * @access private
     * @return boolean
     */
    private function fileExist()
    {
    	$txt  = FCPATH . $this->filePath . $this->str . '.' . date('Y-m-d') . ".txt";
    	
    	if (!file_exists($txt)) {
    		return false;
    	}
    	
    	$json = file($txt);
    	$file = !empty($json[0]) ? json_decode($json[0], true) : '';
    	
    	return !empty($file) ? true : false;
    }
    
    /**
     * 获取设置的crontab信息
     *
     * @access private
     * @return boolean
     */
    private function getCrontab()
    {
    	//获取crontab设置的执行代码
    	$crontab = 'crontab -l';
    	$txt 	 = FCPATH . $this->txtPath . 'crontab.txt';
    	$this->systemShell("$crontab > $txt");
    	$crontab = file($txt);
    	$total 	 = count($crontab);
    	$result  = '';
    	for($i = 0; $i < $total; $i++){
			if (stristr($crontab[$i], $this->str) !== FALSE) {
				$result = explode(' ', trim($crontab[$i]));
				break;
			}
		}
		
		return $result;
    }
    
    /**
     * 执行脚本
     *
     * @access private
     * @return boolean
     */
    private function systemShell($_shell)
    {
    	log_message('error', $_shell, 'shell');
    	return system($_shell);
    }
}