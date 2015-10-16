<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 支付插件总入口
 * zhang xiaojian
 */
class Payment
{
	private $CI = NULL;
	private $pay_type = '';
	private $payname = '';
	//支付方式
	private $all_type=array(
		'baofoo'=>'baofu',
		'yeepay'=>'yibao',
		'llpay' =>'llpay'
		);
	public function __construct($type='',$is_wap = false)
	{
		$this->CI =& get_instance();
		$this->pay_type = $type;
		$this->payname = 'pay';
		if ($is_wap) {
			$this->payname = 'paywap';
		}

		if (isset($this->all_type[$type]) && file_exists(dirname(__FILE__).'/payment/'.$this->all_type[$type].'/'.ucfirst($this->payname).'.php')) {
			$this->CI->load->library('payment/'.$this->all_type[$type].'/'.ucfirst($this->payname));	
		}else{
			redirect('main/index');
		}
	}
	/**
	 * 支付请求函数
	 * @param array  $data 支付数据
	 */
	public function Payment($data=array())
	{
		if (isset($this->all_type[$this->pay_type])) {
			return $this->CI->{$this->payname}->PaySubmit($data);
		}else{
			return '';
		}
	}
	/**
	 * 支付返回通知函数
	 */
	public function Callback(){
		return $this->CI->{$this->payname}->Callback();
	}
	/**
	 * 额外回调同步
	 */
	public function Callback_return()
	{
		return $this->CI->{$this->payname}->Callback_return();
	}
	public function getOrderInfo($order_no,$type='normal')
	{
		return $this->CI->{$this->payname}->getOrderInfo($order_no,$type);
	}
}