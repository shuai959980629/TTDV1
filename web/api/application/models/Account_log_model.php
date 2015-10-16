<?php
/**
 * 用户流水 MODEL
 *
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://mrg.tt1.com.cn)
 * @license	http://mrg.tt1.com.cn
 * @link	http://mrg.tt1.com.cn
 * @since	Version 3.0.0 2015-05-14
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class AccountException extends Exception {}

class AccountNotFoundException extends AccountException {}

class TransIdException extends AccountException {}

class AccountSubtractionException extends AccountException {}

class Account_log_model extends Sharding_Model
{
	/**
	 * 主键ID
	 *
	 * @var string
	 */
	public     $pk    = 'id';

	/**
	 * 时间点 格式：20150501
	 *
	 * @var string
	 */
	public     $pot    = '20150501';

	/**
	 * 时间点 当前一月 格式：20150501
	 *
	 * @var string
	 */
	public     $pot_now = '20150501';

	/**
	 * 时间结点差异
	 *
	 * @var string
	 */
	public     $diffy   = false;

	/**
	 * 业务类型 数组
	 *
	 * @var array
	 */
	public $tobs = array(
		'tender_frozen'				, 	//投标冻结
		'tender_canceled'			,	//投标失败
		'tender_success_capital'	,	//投标成功
		'tender_success_interest'	,	//投标成功利息
		'cash_frozen'				,	//提现冻结
		'cash_canceled'				,	//提现取消
		'cash_success'				,	//提现成功
		'cash_fee'					,	//提现手续费
		'recharge_success'			,	//充值成功
		'recharge_fee'				,	//充值手续费
		'repayment'					,	//还本
		'day_interest'				,	//天天返利息
		'assignment_charge'			,	//债权转让收入
		'assignment_interest'		, 	//债权转让扣除利息
		'assignment_fee'			,	//债权转让手续费
		'assignment_repay_interest'	,	//债权转补当月部分利息[月月返独享]
		'bonus'						,	//奖励
		'borrow_repay'				,	//提前结标
		'borrow_repay_interest'		,	//提前结标利息
	);

	/**
	 * 业务类型关联绑定操作
	 *
	 * @var array
	 */
	public $tobBind = array(
		'recharge_success'		 => array(
			'recharge_success', 		//充值成功
			'recharge_fee'				//充值手续费
		),
		'tender_success_capital' => array(
			'tender_success_capital',	//投标成功
			'tender_success_interest'	//投标成功利息
		),
		'cash_success'			 => array(
			'cash_success', 			//提现成功
			'cash_fee'					//提现手续费
		),
		'assignment_charge'	 => array(
			'assignment_charge', 		//债权转让收入
			'assignment_interest', 		//债权转让扣除利息
			'assignment_fee',			//债权转让手续费
			'assignment_repay_interest'	//债权转补当月部分利息[月月返独享]
		),
		'borrow_repay'	 => array(
			'borrow_repay', 			//提前结标
			'borrow_repay_interest', 	//提前结标利息
		),
	);

	/**
	 * 业务类型 数组
	 *
	 * @var array
	 */
	public $tobArr = array();

	/**
	 * 业务类型
	 *
	 * @var string
	 */
	public $tob = '';

	/**
	 * 用户资金
	 *
	 * @var array
	 */
	public $uact = array(
		'amount'	, 	//当前资金总额
		'balance'	,	//可用余额
		'cash_free'	,	//提现可免除手续费金额
		'frozen'	,	//当前总冻结资金
		'await'		,	//当前总待收
	);

	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct('account_log');
        $this->pot 		= date('Ymd');
        $this->pot_now 	= date('Ymd', strtotime(date('Ym') . '01'));
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
     * 增加数据
     *
     * @param  array $fields	//本次操作用户金额数据
     * @access public
     * @return boolean
     */
    public function createRow($fields)
    {
    	$this->tobArr = explode(',', $fields['tob']);

    	//开启事务
    	$this->db->trans_begin();

        //事务过程当中调用的代码里，如果有抛出异常情况，
        //要在这里把异常处理了，将事务回滚，解除被锁定的行；
        try {
    	    $data = $this->setQuery($fields);
        } catch (AccountException $e) {
            $this->db->trans_rollback();
            throw $e;
        }

    	//成功->事务完成，失败->事务回滚
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			throw new AccountException('Trans_Status_Is_False');
		} else {
			$this->db->trans_commit();
		}
		
		return $data;
    }

    /**
     * SQL组合-调用-执行函数
     *
     * @param  array $fields	//本次操作用户金额数据
     * @access private
     * @return boolean
     */
    private function setQuery($fields)
    {
    	//查询user account信息
    	$this->db->where('uid', $fields['uid']);
    	$sql  = $this->db->get_compiled_select('user_account');
		$sql .= " FOR UPDATE";
		
    	$userAccount = $this->db->query($sql)->row_array();
        if ($userAccount === null) {
    		throw new AccountNotFoundException('User Account is NULL');
    	}

    	//插入流水表
    	$this->pot 	 = date('Ym', strtotime($this->pot)) . '01';
    	$this->table = $this->getTable($this->pot);
    	//两时间结点是否存在差异
    	$this->pot != $this->pot_now && $this->diffy = true;
    	
    	$data = $this->getData($userAccount, $fields);
    	//重新定位表
    	$this->diffy && $this->table = $this->getTable($this->pot_now);
		//入库流水表
    	foreach ($data as $value) {
    		$sql = $this->db->set($value)->get_compiled_insert($this->table);
    		$this->db->query($sql);
    	}

    	//修改user account数据
    	$arr 	 = array();
    	$arrBack = array('uid' => $fields['uid']);
    	$array   = array_pop($data);
    	foreach ($this->uact as $value) {
    		if (isset($array[$value . '_after'])) {
    			$arrBack[$value] = floatval($array[$value . '_after']);
    			if (isset($userAccount[$value]) && floatval($userAccount[$value]) != $arrBack[$value]) {
    				$arr[$value] = $arrBack[$value];
    			}
    		}
    	}
    	//如果用户总资金项目发生变化才执行修改
    	if (!empty($arr)) {
    		$where 	= array('uid' => (int) $fields['uid']);
	    	$sql 	= $this->db->set($arr)->where($where)->get_compiled_update('user_account');
	    	$this->db->query($sql);
	    	unset($sql);
    	}
    	
    	return $arrBack;
    }

    /**
     * 组合数据
     *
     * @param  array $userAccount	//用户金额总数据
     * @param  array $fields		//本次操作用户金额数据
     * @access private
     * @return array
     */
    private function getData($userAccount, $fields)
    {
    	$money = strpos($fields['money'], ',') ? explode(',', $fields['money']) : array($fields['money']);

    	if (isset($this->tobBind[$this->tobArr[0]])) {
    		//如果只传了多个money 和 一个tob的情况
    		if (count($money) > count($this->tobArr)) {
    			$tobs = $this->tobBind[$this->tobArr[0]];
    		} else {
    			$tobs = $this->tobArr;
    		}
    	} elseif (in_array($this->tobArr[0], $this->tobs)) {
    		$tobs = $this->tobArr;
    	} else {
    		throw new AccountException('TOB_NAME_IS_NOT_FIND');
    	}
    	
    	foreach ($money as $key=>$value) {
    		//不能传负数
    		if ($value < 0) {
    			throw new AccountException('VALUE_IS_NOT_NEGATIVE');
    		}
    		
    		if (!isset($tobs[$key])) {
    			throw new AccountException('TOB_KEY_IS_NOT_FIND');
    		} elseif (!in_array($tobs[$key], $this->tobs)) {
    			throw new AccountException('TOB_NAME_IS_NOT_FIND');
    		} elseif ($key > 0) {
    			$userAccount = array();
    			foreach ($this->uact as $v) {
		    		if (isset($arr[$v . '_after'])) {
		    			$userAccount[$v] = $arr[$v . '_after'];
		    		}
		    	}
    		}
    		
    		$this->tob = $tobs[$key];
    		
    		if (!method_exists($this, $this->tob)) {
	    		throw new AccountException("{$this->tob} Is Undefined function");
	    	}

    		$arr = array(
	    		'id'			=> unique_id(),
	    		'uid' 			=> $fields['uid'],
	    		'money' 		=> $value,
	    		'tob'			=> $this->tob,
	    		'rel_data_id'	=> $fields['rel_data_id'],
	    		'created'		=> date('Y-m-d H:i:s'),
	    		'trans_id'		=> $this->tob . '_' . $fields['trans_id']
	    	);

	    	$this->db->where('trans_id', $this->tob . '_' . $fields['trans_id']);
            if ($this->db->get($this->table)->row_array()) {
	    		throw new TransIdException('Trans_id Already Exists');
	    	}
	    	//再次查询当前表是否存在记录
	    	if ($this->diffy) {
	    		$this->db->where('trans_id', $this->tob . '_' . $fields['trans_id']);
	    		if ($this->db->get($this->getTable($this->pot_now))->row_array()) {
		    		throw new TransIdException('Trans_id Already Exists Current Table');
		    	}
	    	}

	    	foreach ($userAccount as $k=>$val) {
	    		if (in_array($k, $this->uact)) {
	    			$arr[$k . '_before'] = $val;
	    			$this->{$this->tob}($k, $val, $value, $arr);
	    		}
	    	}

	    	$data[] = $arr;
    	}

    	return $data;
    }

    /**
     * 充值操作 [充值成功:总金额增加、可用余额增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function recharge_success($key, $value, $money, &$data)
    {
    	if (in_array($key, array('amount', 'balance'))) {
			$data[$key . '_after']  = $value + $money;
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 充值手续费 [充值手续费:总金额减少、可用余额减少]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function recharge_fee($key, $value, $money, &$data)
    {
    	if (in_array($key, array('amount', 'balance'))) {
			$this->subtraction($key, $value, $money, $data);
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 投标冻结 [投标冻结:可用余额减少、总冻结资金增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function tender_frozen($key, $value, $money, &$data)
    {
    	if ($key == 'balance') {
    		$this->subtraction($key, $value, $money, $data);
    	} elseif ($key == 'frozen') {
    		$data[$key . '_after']  = $value + $money;
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 投标失败 [投标失败:可用余额增加、总冻结资金减少]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function tender_canceled($key, $value, $money, &$data)
    {
    	if ($key == 'balance') {
    		$data[$key . '_after']  = $value + $money;
    	} elseif ($key == 'frozen') {
    		$this->subtraction($key, $value, $money, $data);
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 投标成功 [投标成功:总待收增加(+本金)、总冻结资金减少(-本金)]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function tender_success_capital($key, $value, $money, &$data)
    {
    	if ($key == 'await') {
			$data[$key . '_after']  = $value + $money;
		} elseif ($key == 'frozen') {
			$this->subtraction($key, $value, $money, $data);
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 投标成功利息 [投标成功:总待收增加(+收益)、资金总额增加(+收益)]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function tender_success_interest($key, $value, $money, &$data)
    {
    	if (in_array($key, array('amount', 'await'))) {
			$data[$key . '_after']  = $value + $money;
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 提现冻结 [提现冻结:可用余额减少、总冻结资金增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    public function cash_frozen($key, $value, $money, &$data)
    {
    	if ($key == 'balance') {
			$this->subtraction($key, $value, $money, $data);
		} elseif ($key == 'frozen') {
			$data[$key . '_after']  = $value + $money;
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 提现取消 [提现取消:可用余额增加、总冻结资金减少]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function cash_canceled($key, $value, $money, &$data)
    {
    	if ($key == 'balance') {
			$data[$key . '_after']  = $value + $money;
		} elseif ($key == 'frozen') {
			$this->subtraction($key, $value, $money, $data);
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 提现成功 [提现成功:资金总额减少、总冻结资金减少]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function cash_success($key, $value, $money, &$data)
    {
    	if (in_array($key, array('frozen', 'amount'))) {
    		$this->subtraction($key, $value, $money, $data);
		} elseif ($key == 'cash_free') {
			$data[$key . '_after']  = $value > $money ? bcsub($value, $money, 3) : 0;
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 提现手续费 [提现手续费:资金总额减少、总冻结资金减少]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function cash_fee($key, $value, $money, &$data)
    {
    	if (in_array($key, array('frozen', 'amount'))) {
			$this->subtraction($key, $value, $money, $data);
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 还本 [还本:可用余额增加、总待收减少、可免除手续费金额增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function repayment($key, $value, $money, &$data)
    {
    	if (in_array($key, array('balance', 'cash_free'))) {
    		$data[$key . '_after']  = $value + $money;
    	} elseif ($key == 'await') {
    		$this->subtraction($key, $value, $money, $data);
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 天天返利息 [天天返利息:可用余额增加、总待收减少、可免除手续费金额增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function day_interest($key, $value, $money, &$data)
    {
    	if (in_array($key, array('balance', 'cash_free'))) {
    		$data[$key . '_after']  = $value + $money;
    	} elseif ($key == 'await') {
    		$this->subtraction($key, $value, $money, $data);
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 债权转让收入 [债权转让收入:可用余额增加(+本金)、可免除手续费金额增加、总待收减少(-本金)]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function assignment_charge($key, $value, $money, &$data)
    {
		if (in_array($key, array('balance', 'cash_free'))) {
    		$data[$key . '_after']  = $value + $money;
    	} elseif ($key == 'await') {
    		$this->subtraction($key, $value, $money, $data);
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 债权转让收入收益操作 [债权转让收入:资金总额减少(-剩余收益)、总待收减少(-剩余收益)]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function assignment_interest($key, $value, $money, &$data)
    {
    	if (in_array($key, array('amount', 'await'))) {
    		$this->subtraction($key, $value, $money, $data);
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 债权转让手续费 [债权转让手续费:资金总额减少、可用余额减少]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function assignment_fee($key, $value, $money, &$data)
    {
    	if (in_array($key, array('balance', 'amount'))) {
			$this->subtraction($key, $value, $money, $data);
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 债权转补当月部分利息 [月月返独享、债权转让补利息:可用余额增加、可免除手续费金额增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function assignment_repay_interest($key, $value, $money, &$data)
    {
    	if (in_array($key, array('balance', 'cash_free', 'amount'))) {
    		$data[$key . '_after']  = $value + $money;
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 奖励 [奖励:资金总额增加、可用余额增加、可免除手续费金额增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function bonus($key, $value, $money, &$data)
    {
    	if (in_array($key, array('balance', 'amount', 'cash_free'))) {
			$data[$key . '_after']  = $value + $money;
		} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 提前结标 [提前结标:总待收减少(-本金)、可用余额增加(+本金)、可免除手续费金额增加]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function borrow_repay($key, $value, $money, &$data)
    {
		if (in_array($key, array('balance', 'cash_free'))) {
    		$data[$key . '_after']  = $value + $money;
    	} elseif ($key == 'await') {
    		$this->subtraction($key, $value, $money, $data);
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 提前结标利息 [提前结标利息:总待收减少(-收益)、资金总额减少(-收益)]
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function borrow_repay_interest($key, $value, $money, &$data)
    {
		if (in_array($key, array('await', 'amount'))) {
    		$this->subtraction($key, $value, $money, $data);
    	} else {
			$data[$key . '_after']  = $value;
		}
    }

    /**
     * 执行减法操作 否则抛出异常
     *
     * @param  string  $key		//原有对应金额项目名称
     * @param  decimal $value	//原有金额
     * @param  decimal $money	//本次操作金额
     * @param  array   $data	//组合数组
     * @access private
     * @return void
     */
    private function subtraction($key, $value, $money, &$data)
    {
    	if ($value < $money) {
    		$EX = strtoupper($this->tob) . ':' . strtoupper($key) . '_IS_NOT_ENOUGH' . ':' . $value  . ':' . $money;
			throw new AccountSubtractionException($EX);
		} else {
			$data[$key . '_after'] = bcsub($value, $money, 3);
		}
    }

    /**
     * 获取指定ID数据
     *
     * @param  integer $tid  //流水表Trands_ID号
     * @param  string  $tob  //业务类型
     * @param  string  $pot  //时间结点
     * @access public
     * @return array
     */
    public function getRow($tid, $tob, $pot)
    {
    	if (!$this->table = $this->getTable($pot)) {
    		throw new AccountException('NOT_FIND_TABLE_NAME');
    	}
    	$this->db->where('trans_id', strtolower($tob) . '_' . $tid);
    	$query = $this->db->get($this->table);

    	return $query->row_array();
    }

    /**
     * 分页查询
     *
     * @param  array 	$where	//查询条件
     * @param  integer	$limit	//查询条数
     * @param  integer 	$offset	//偏移量
     * @access public
     * @return array
     */
    public function search($where = array(), $limit = 20, $offset = 0)
    {
    	$orderBy   = 'DESC';
        $startTime = $endTime = '';
    	if (!empty($where['startTime'])) {
    		$startTime = $this->pot = $where['startTime'];
    		$orderBy   = 'ASC';
    		$this->db->where('created >= ', $where['startTime']);
    	} elseif (!empty($where['endTime'])) {
    		$endTime = $this->pot = $where['endTime'];
    		$orderBy = 'DESC';
    		$this->db->where('created <= ', $where['endTime']);
    	}
    	unset($where['startTime']);
    	unset($where['endTime']);

    	if (!$this->table = $this->getTable($this->pot)) {
    		throw new AccountException('NOT_FIND_TABLE_NAME');
    	}

    	$this->db->where($where);
    	$total = $this->db->count_all_results($this->table);

        if ($startTime != '') {
            $this->db->where('created >= ', $startTime);
        } elseif ($endTime != '') {
            $this->db->where('created <= ', $endTime);
        }
        
    	$this->db->where($where);
    	$this->db->order_by("{$this->table}.{$this->pk} {$orderBy}");
    	$this->db->limit($limit, $offset);

        $query = $this->db->get($this->getTable($this->pot));

        $data = array(
        	'result' => $query->result_array(),
        	'total'	 => $total
        );

        return $data;
    }
}
?>