<?php
/**
 * 用户资金 MODEL
 *
 * @package		MODEL
 * @author		LEE
 * @copyright	Copyright (c) 2015
 * @license		图腾贷
 * @link		http://www.tutengdai.com
 * @since		Version 1.0.0 2015-10-15
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Cash_free_model extends Sharding_Model
{
	/**
	 * 业务类型
	 *
	 * @var array
	 */
	private $business = array(
		'tender_success_capital'	,	//投标成功
		'cash_success'				,	//提现成功
		'recharge_success'			,	//充值成功
		'repayment'					,	//还本
		'day_interest'				,	//天天返利息
		'bonus'						,	//奖励
		'borrow_repay'				,	//提前结标
		'borrow_repay_interest'		,	//提前结标利息
    );
    
    /**
     * 业务类型对应文献
     *
     * @var array
     */
    private $businessTxt = array(
		'tender_success_capital'	=> '投标成功',
		'cash_success'				=> '提现成功',
		'recharge_success'			=> '充值成功', 
		'repayment'					=> '还本',
		'day_interest'				=> '天天返利息',
		'bonus'						=> '奖励',
		'borrow_repay'				=> '提前结标',
		'borrow_repay_interest'		=> '提前结标利息',
    );
    
    /**
     * 用户点击获取可免金额来源
     *
     * @var array
     */
    private $frag = array(
    	0 => '网站:用户点击弹窗看',
    	1 => '网站:最后确定数据',
    	2 => '微信:用户点击弹窗看',
    	3 => '微信:最后确定数据',
    );
    
    /**
     * 充值金额存储器
     *
     * @var decimal
     */
    private $recharge = 0;
    
    /**
     * 可免费金额存储器
     *
     * @var decimal
     */
    private $cashFree = 0;
    
    /**
     * 单条数据存储器
     *
     * @var array
     */
    private $row = array();
    
    /**
     * 读取总数据存储器
     *
     * @var array
     */
    private $accountLogs = array();
    
    /**
     * 传参存储器
     *
     * @var array
     */
	public $fields = array();
	
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
     * 定位分表
     *
     * @param  string $pot	//时间结点
     * @access public
     * @return string
     */
    public function getTable($pot)
    {
        return $this->get_table('account_log', $pot);
    }
    
    /**
     * 调用方法 获取可免费金额
     *
     * @param  array $fields	//传输参数
     * @return number
     */
    public function getCashFree($fields)
    {
    	if (!isset($fields['uid'])) {
    		return $this->recharge;
    	}
    	
    	//存储传进来的参数
    	$this->fields = $fields;
    	//确定表
    	$month	 = date('Y-m');
    	$today   = date('Y-m-d');
    	$diff    = 86400 * 15;
    	$created = strtotime($today) - $diff;
    	$month2	 = date('Y-m', $created);
    	$created = date('Y-m-d', $created);
    	//确定是2个标还是1个表后存入容器
    	$pots = array();
    	if ($month2 == $month) {
    		$pots[] = date('Ym', strtotime($month)) . '01';
    	} else {
    		$pots = array(
    			date('Ym', strtotime($month2)) . '01',
    			date('Ym', strtotime($month))  . '01'
    		);
    	}
    	//开始读取数据
    	foreach ($pots as $pot) {
    		$this->getResult($pot, $created);
    	}
    	//无数据 返回初始数据
    	if (empty($this->accountLogs)) {
    		return $this->recharge;
    	}
    	//前15日起始数据 取本条数据的 balance_after 为起始可免金额
    	$init = array_shift($this->accountLogs);
    	$this->cashFree = $init['balance_after'];
    	//开始对各种业务类型数据进行加减
    	$frag = isset($this->frag[$this->fields['step']]) ? $this->frag[$this->fields['step']] : '';
    	$msg  = "==========================={$frag}================================";
    	foreach ($this->accountLogs as $value) {
    		$this->row = $value;
    		$this->$value['tob']($value['money']);
    		$msg = $this->msg($value['money'], $msg);
    	}
    	$msgName = "cash/" . $this->fields['uid'] . "_cash_free_msg";
    	log_message('error', $msg, $msgName);
    	
    	return $this->cashFree;
    }
    
    /**
     * 读取流水数据
     *
     * @param string $pot		//时间结点
     * @param string $created	//时间点 [作为判断的起始条件]
     */
    private function getResult($pot, $created)
    {
    	$table = $this->getTable($pot);
    	
    	$this->db->select('balance_after, money, tob, created');
    	$this->db->where('uid', $this->fields['uid']);
    	$this->db->where('created >', $created);
    	$this->db->where_in('tob', $this->business);
    	
    	$sql = $this->db->get_compiled_select($table);
    	$rs  = $this->db->query($sql)->result_array();
    	
    	if (!empty($rs)) {
    		$this->accountLogs = !empty($this->accountLogs) ? array_merge($this->accountLogs, $rs) : $rs;
    	}
    }
    
    /**
     * 记录计算流程 [共对账用]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function msg($money, $msg)
    {
    	$tob  = strtolower($this->row['tob']);
    	$msg .= "\r\n";
    	$msg .= "操作时间：{$this->row['created']}\t";
    	$msg .= "操作方法：{$this->businessTxt[$tob]}\t";
    	$msg .= "操作金额：{$money}\t";
    	$msg .= "可免金额：{$this->cashFree}\t";
    	$msg .= "充值额度：{$this->recharge}\t";
    	$msg .= "\r\n";
    	return $msg;
    }
    
    /**
     * 加法操作
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function add($money)
    {
    	$this->cashFree = bcadd($this->cashFree, $money, 3);
    }
    
    /**
     * 减法操作
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function sub($money)
    {
    	if ($this->recharge >= $money) {
    		$this->recharge = bcsub($this->recharge, $money, 3);
    	} else {
    		$total = bcadd($this->cashFree, $this->recharge, 3);
    		$this->cashFree = $total > $money ? bcsub($total, $money, 3) : 0;
    		$this->recharge = 0;
    	}
    }
    
    /**
     * 投标成功
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function tender_success_capital($money)
    {
    	$this->sub($money);
    }
    
    /**
     * 提现成功 [加法操作]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function cash_success($money)
    {
    	$this->cashFree = $this->cashFree > $money ? bcsub($this->cashFree, $money, 3) : 0;
    }
    
    /**
     * 充值成功 [加法操作]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function recharge_success($money)
    {
    	$this->recharge = bcadd($this->recharge, $money, 3);
    }
    
    /**
     * 还本 [加法操作]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function repayment($money)
    {
    	$this->add($money);
    }
    
    /**
     * 天天返利息 [加法操作]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function day_interest($money)
    {
    	$this->add($money);
    }
    
    /**
     * 奖励 [加法操作]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function bonus($money)
    {
    	$this->add($money);
    }
    
    /**
     * 提前结标 [加法操作]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function borrow_repay($money)
    {
    	$this->add($money);
    }
    
    /**
     * 提前结标利息 [加法操作]
     *
     * @param  decimal $money	//操作金额
     * @return void
     */
    private function borrow_repay_interest($money)
    {
    	$this->add($money);
    }
}