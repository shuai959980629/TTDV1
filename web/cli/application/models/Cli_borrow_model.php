<?php
/**
 * CLI 贷款标 MODEL
 *
 * @package	MODEL
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://cli.tt1.com.cn)
 * @license	http://cli.tt1.com.cn
 * @link	http://cli.tt1.com.cn
 * @since	Version 3.0.0 2015-07-02
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Cli_borrow_model extends Sharding_Model
{
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct('borrow');
        
        $this->load->model('tender_log_model', 'tender_log');
        $this->load->model('borrow_model'	 , 'borrow');
        $this->load->model('api_fund_model'	 , 'api_fund');
    }
    
    /**
     * 获取返本标信息
     * [当日满标数据不会返回其中]
     * 
     * @access public
     * @return array
     */
    public function getBorrow()
    {
    	//正常标
    	$where = array(
    		'cols' 	=> array('id', 'reverified_time'),
    		'in' 	=> array('status' => array('repayment')),	//还款中
    	);
    	$rs1 = $this->borrow->getRows($where);
    	empty($rs1) && $rs1 = array();
    	//特权标 【特权标无法在读取时判断是不是当日投当日还息，交由还息内部处理】
    	$rule = array(
    		'cols' 	 		=> array('id', 'reverified_time'),
    		'status' 		=> 'verified',
    		'member_flag' 	=> 'try',
    	);
    	$rs2 = $this->borrow->getRows($rule);
    	empty($rs2) && $rs2 = array();
    	
    	$borrow = array_merge($rs1, $rs2);
    	//当日时间戳
    	$today  = strtotime(date('Ymd'));
    	
    	$result = array();
    	foreach ($borrow as $value) {
    		if ($value['reverified_time'] < $today) {
    			$result[$value['id']] = $value['id'];
    		}
		}
		
    	return $result;
    }
    
    /**
     * 48小时流标操作
     *
     * @param  array $borrow	//标数据
     * @return boolean
     */
    public function canceled($borrow)
    {
    	if (empty($borrow)) {
    		return false;
    	}
    	
    	//判断是否超过48小时
    	if ((time() - $borrow['verified_time']) / 3600 <= 48) {
    		return true;
    	}
    	//修改标状态
    	$this->borrow->update($borrow['id'], array('status' => 'failure'));
    	//对投标人的相关操作
    	$where = array(
    		'cols'		=> array('id', 'uid', 'capital'),
    		'borrow_id' => $borrow['id']
    	);
    	$tender = $this->tender_log->getRows($where);
    	//载入语言包
    	$this->config->load('account_status');
    	$account_status = $this->config->item('account_status');
    	
    	foreach ($tender as $value) {
    		//修改投资状态 [流标]
        	$arr = array('status' => 'canceled');
        	if ($this->tender_log->update($value['id'], $arr)) {
        		//操作资金流水
	    		$param = array(
		            'uid'			=> $value['uid'],
		            'money'	    	=> "{$value['capital']}",
		            'tob'			=> "tender_canceled",
		            'rel_data_id'	=> $value['id'],
		            'trans_id'		=> $value['id'],
		            'pot'			=> date('Ymd'),
		        );
		        $api = $this->api_fund->send($param);
		        //写显示流水 [投资人]
	        	$account_log = array(
					'rel_data'      => array(
						'money'		=> $value['capital'],
						'title'		=> $borrow['title'],
						'account'	=> isset($api['data']['balance']) ? $api['data']['balance'] : 0,
						'logs' => array(
								1 => array(
									'status'	=> $account_status['tender_canceled'],
									'success'	=> 1,
									'created'	=> date("Y-m-d H:i:s")
								),
							)
						),
					'rel_type'      => "tender",
					'uid'           => $value['uid'],
					'ticket_id'     => $value['id'],
				);
				Event::trigger('user_account_change', $account_log);
        	}
    	}
    	//修改转让人投资数据
		$this->tender_log->update($borrow['tender_id'], array('creditor' => 'canceled'));
    	//获取转让人资金数据
		$where  = array('uid' => $borrow['uid']);
        $opm    = 'Acc';
        $rs		= $this->api_fund->getRow($where, $opm);
        //写显示流水 [转让人]
    	$accountLog = array(
			'rel_data'      => array(
				'money'		=> $borrow['amount'],
				'title'		=> $borrow['title'],
				'account'	=> isset($rs['data']['balance']) ? $rs['data']['balance'] : 0,
				'logs' 		=> array(
						'data'	=> array(
							2 => array(
								'status'	=> $account_status['tender_canceled'],
								'success'	=> 1,
								'created'	=> date("Y-m-d H:i:s")
							)
						)
					)
				),
			'rel_type'      => 'nassignment',
			'uid'           => $borrow['uid'],
			'ticket_id'     => $borrow['tender_id'],
		);
		Event::trigger('user_account_change', $accountLog);
    	
    	return true;
    }
}