<?php
/**
 * 用户资金操作接口 [用户资金表、资金流水表] MODEL
 *
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://mrg.tt1.com.cn)
 * @license	http://mrg.tt1.com.cn
 * @link	http://mrg.tt1.com.cn
 * @since	Version 3.0.0 2015-05-18
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_fund_model extends CI_Model
{
	/**
	 * 请求域名
	 * 
	 * @var string
	 */
	private $url = array(
		'default' 			=> 'http://api.tutengdai.com/',
		'www.tutengdai.com'	=> 'http://api.tutengdai.com/',
		'mrg.tutengdai.com'	=> 'http://api.tutengdai.com/',
	);
	
	/**
	 * 发送参数数组
	 *
	 * @var array
	 */
	private $send = array();

	/**
	 * 提供两表操作读取
	 *
	 * @var array
	 */
	private $opm = array(
		'Acc' 	,
		'AccLog',
	);

	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct();
    }

    /**
     * 发起请求
     *
     * @access private
     * @return array
     */
    private function sendOP($url)
    {
    	//记录调用链接，并记录请求来源地址
    	log_message('error', $url . " \r\n " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 'curl-request');
    	
    	if (isset($this->send['method']) && $this->send['method'] == 'POST') {
    		
    		$data_url = http_build_query($this->send);
		    $data_len = strlen($data_url);
		    
	        $opts = array(
	        	'http' => array(
				    'method'  => "POST",
				    'header'  => "Connection: close\r\nContent-Length: $data_len\r\n",
				    'content' => $data_url,
				    'timeout' => 30
			    )
			);
			
	        $context = stream_context_create($opts);
	        
	        $result = @file_get_contents($url, false, $context);
	        
	        //json数据返回错误收集器
			$data = $this->jsonErrorCollect($result);
			
    	} else {
    		$opts = array(
	        	'http' => array(
				    'method'  => "GET",
				    'header'  => "Accept-language: zh-cn\r\n",
				    'timeout' => 5
			    )
			);
			
	        $context = stream_context_create($opts);
	        
    		$result = @file_get_contents($url, false, $context);
    		
			//json数据返回错误收集器
			$data = $this->jsonErrorCollect($result);
			
	    	$msg  = !empty($data) && $data !== null ? urldecode(http_build_query($data)) : '';
	    	log_message('error', $msg, 'curl-response');
    	}
    	
    	if ($data === null) {
    		$data = array(
    			'error' => 0,
    			'msg'	=> '系统错误:接口请求失败,可能接口地址不正确'
    		);
    	}
    	
    	return $data;
    }
    
    /**
     * json数据返回错误收集器
     *
     * @param  json $result //json字符串
     * @access private
     * @return void
     */
    private function jsonErrorCollect($result)
    {
    	$data = json_decode($result, true);
    	
    	switch (json_last_error()) {
	        case JSON_ERROR_NONE:
	        	//没有错误发生
		        break;
	        case JSON_ERROR_DEPTH:
	        	$msg = '到达了最大堆栈深度';
		        break;
	        case JSON_ERROR_STATE_MISMATCH:
	        	$msg = '无效或异常的 JSON';
		        break;
	        case JSON_ERROR_CTRL_CHAR:
	            $msg = '控制字符错误，可能是编码不对';
		        break;
	        case JSON_ERROR_SYNTAX:
	            $msg = '语法错误，可能为带BOM错误';
	            
	            //检查是否带bom返回
		    	if(preg_match('/^\xEF\xBB\xBF/', $result)) {
				    $data = json_decode(trim(substr($result, 3)), true);
				}
				
		        break;
	        case JSON_ERROR_UTF8:
	            $msg = '异常的 UTF-8 字符，也许是因为不正确的编码';
		        break;
	        default:
		        break;
	    }
	    
	    !empty($msg) ? log_message('error', $msg, 'json-error') : '';
	    
	    return $data;
    }

    /**
     * 编译URL
     *
     * @param  string	$uri	//访问方法
     * @access private
     * @return array
     */
    private function resolveUrl($uri)
    {
    	$url = !empty($_SERVER['SERVER_NAME']) && isset($this->url[$_SERVER['SERVER_NAME']]) ? $this->url[$_SERVER['SERVER_NAME']] : $this->url['default'];
        return isset($this->send['method']) && $this->send['method'] == 'POST' ? $url . $uri : $url . $uri . '?' . urldecode(http_build_query($this->send));
    }

    /**
     * 发起一个操作资金请求
     *
     * @param  array $send	//请求参数
     * @access public
     * @return array
     */
    public function send($send)
    {
    	$this->send = $send;
    	
    	$url = $this->resolveUrl('funds/setFunds/');

    	return $this->sendOP($url);
    }

    /**
     * 发起一个单条查询请求
     *
     * @param  array $send	//请求参数
     * @param  strin $opm	//操作表选择 [Acc、AccLog]
     * @access public
     * @return array
     */
    public function getRow($send, $opm)
    {
    	$this->send = $send;
    	
    	$url = $this->resolveUrl("funds/get{$opm}Row/");

    	return $this->sendOP($url);
    }

    /**
     * 发起一个多条查询请求
     *
     * @param  array $send	//请求参数
     * @param  strin $opm	//操作表选择 [Acc、AccLog]
     * @access public
     * @return array
     */
    public function getSearch($send, $opm)
    {
    	$this->send = $send;
    	
    	$url = $this->resolveUrl("funds/get{$opm}Search/");

    	return $this->sendOP($url);
    }

    /**
     * 创建一条用户资金总量数据
     *
     * @param  array | integer  $send	//传递参数 | 非数组默认为UID
     * @access public
     * @return array
     */
    public function createUserAccount($send)
    {
    	if (!is_array($send)) {
    		$send = array('uid' => $send);
    	}
    	
    	$this->send = $send;

    	$url = $this->resolveUrl("funds/setUserAccount/");

    	return $this->sendOP($url);
    }
    
    /**
     * 通用接口调用函数：指定传入链接地址调用
     * 
     * @param  array  $send //传入参数
     * @param  string $url  //自定义请求链接地址，如果有的话，没就报错撒
     * @access public
     * @return array
     */
    public function getUniversalInterface($send, $url)
    {
    	$this->send = $send;
    	$url = $this->resolveUrl($url);
    	
    	return $this->sendOP($url);
    }
}
