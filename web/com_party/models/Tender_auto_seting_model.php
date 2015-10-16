<?php 
/**
 * 自动投标 MODEL
 * 
 * @package		MODEL
 * @author		LEE [整理]
 * @copyright	Copyright (c) 2015 tt1_p2p (http://www.tutengdai.com)
 * @license		图腾贷
 * @link		http://www.tutengdai.com
 * @since		Version 3.0.0 2015-08-24
 * @filesource
 */
if ( ! defined('BASEPATH')) exit('No direct script access allowed' );

class Tender_auto_seting_model extends Base_model 
{
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
    public function __construct()
    {
        parent::__construct('tender_auto_seting');
        $this->pk = 'uid';
    }
    
    /**
     * 符合自动投标的用户
     *
     * @param  array $borrow	//标信息
     * @access public
     * @author 猴哥 [LEE 整理 20150824]
     * @return array
     */
    public function get_match_user($borrow)
    {
        $all = $this->getWidgetRows(array('in'=>array('status'=>array(1, 2)), 'order'=>array('rank_time' => 'asc')));
        $match_user = array();
        foreach($all as $k=>$v){
            if($v['status'] == 1 && $v['apr_min'] <= $borrow['apr'] 
            	&& $v['apr_max'] >= $borrow['apr'] && $v['period_min'] <= $borrow['period'] 
            		&& $v['period_max'] >= $borrow['period'] 
            			&& ($v['deal_flag'] == 'all' || $v['deal_flag'] == $borrow['deal_flag'])){
                $match_user[$k] = $all[$k];
            }
        }

        return $match_user;
    }
    
    /**
	 * 用户的综合数据 [用户总资金、用户可用资金、用户总待收资金、排名、排队总人数、排在该用户前的资金、排队总资金、正在排队用户[一月标、二月标、三月标、抵押标、不限标]]
	 *
	 * @param  integer 	$uid		//用户UID [必填]
	 * @param  string 	$expire		//过期时间，使用SESSION存储的数据，单位秒 [非必填]
	 * @param  array 	$rule		//查询条件 [自动获取]
	 * @param  array 	$data		//数据集	[自动获取]
	 * @param  integer 	$i			//计数器	[自动获取]
	 * @author LEE
	 * @copyright 20151012
	 * @return array
	 */
    public function getAutobid($uid, $expire = 0, $rule = array(), $data = array(), $i = 0)
    {
    	//启用SESSION记录 时间由程序员自行控制
		if (!$i && isset($_SESSION['complex']) && isset($_SESSION['complex']['expire']) 
			&& (time() - $_SESSION['complex']['expire']) < $expire) {
			return $_SESSION['complex'];
		}
		
		if (empty($uid)) {
			return false;
		}
		
		$this->load->model('tender_auto_seting_model', 'tender_auto_seting');
		$this->load->model('api_fund_model'	 	   	 , 'api_fund');
		
		//当前排名
		if (empty($i)) {
			$this->tender_auto_seting->pk = 'uid';
	    	$autobid   = $this->tender_auto_seting->get($uid);
	    	$rank_time = !empty($autobid['status']) ? $autobid['rank_time'] : time() * 10000;
		}
		
		//初始化变量
    	$fundBefore = $balance = $rank = $await = $amount = 0;
    	//用户资金收集、用户UID收集、自动投标数据收集
    	$_USER_ACC = $_UIDS = $rows = array();
    	
    	//初始化统计各种标字段
    	if (empty($data['borrow'])) {
    		$data['borrow'] = array(
				'one' 	=> 0,
				'two' 	=> 0,
				'three' => 0,
				'pawn'  => 0,
				'any' 	=> 0,
			);
    	}
    	extract($data['borrow']);
    	
    	//在自己之前的用户[含本人]
    	if (empty($rule)) {
    		$rule = array(
    			'in'	 => array('status' => array(1, 2)),
    			'scope'  => array('mtt' => array('rank_time' => $rank_time))
    		);
    	}
    	//查询自动投标设置表数据
    	$result = $this->tender_auto_seting->getWidgetRows($rule);
    	if (!empty($result)) {
			//调用缓存
	    	$this->load->driver('cache');
	    	$cacheName    = 'userAccountCache' . $i . 'uid' . $uid;
	    	$cacheNameAcc = $uid . '_account_' . $i;
	    	
    		foreach ($result as $value) {
    			//抵押标 总人数统计
				$value['deal_flag'] == 'mortgage' && $pawn += 1;
				//一月标 总人数统计
				$value['period_min'] == 30 && $one += 1;
				//二月标 总人数统计
				$math = ($value['period_min'] + $value['period_max']) / 60;
				1 < $math && $math < 3 && $two += 1;
				//三月标 总人数统计
				$value['period_max'] == 90 && $three += 1;
				//不限 总人数统计
				$value['period_max'] > 90 && $any += 1;
				
				//用户自动投标数据收集
				$rows[$value['uid']] = $value;
				
				//用户UID收集
				$_UIDS[] = $value['uid'];
    		}
    		
    		if (!empty($_UIDS)) {
    			//读取所以用户的资金数据
				$request = $this->cache->memcached->get($cacheName);
				if (!empty($request)) {
					$request = !empty($request) ? unserialize(gzuncompress($request)) : array();
				}
				//读取失败或过期重新读取数据库
				if (empty($request)) {
					//请求接口获取用户资金数据
					$where  = array(
			    		'uid'		=> implode($_UIDS, ','),//UID字符串
			    		'limit'		=> count($_UIDS),		//读取条数
			    		'offset'	=> 0,					//偏移量
			    		'method'	=> 'POST'				//请求方式
			    	);
			    	$opm     = 'Acc';
			    	$request = $this->api_fund->getSearch($where, $opm);
				}
				
		    	if ($request['error'] == 1) {
		    		foreach ($request['data']['result'] as $value) {
		    			if (isset($rows[$value['uid']]) 
		    				&& $rows[$value['uid']]['tender_money_min'] <= $value['balance']
		    					&& $rows[$value['uid']]['status'] == 1) {
		    				//统计排名资金
	    					$fundBefore += $rows[$value['uid']]['tender_money_max'] < $value['balance'] 
		    					? $rows[$value['uid']]['tender_money_max'] 
		    					: $value['balance'];
	    				}
	    				//存储用户资金数据
	    				$_USER_ACC[$value['uid']] = $value;
		    		}
		    		//缓存所以用户的资金数据
		    		$this->cache->memcached->save($cacheNameAcc, gzcompress(serialize($_USER_ACC), 9), 10);
		    		
    				//用户资金数据
	    			if (isset($_USER_ACC[$uid])) {
	    				//用户可用资金
	    				$balance = !empty($_USER_ACC[$uid]['balance']) ? numberFormat($_USER_ACC[$uid]['balance']) : 0;
	    				//用户总待收资金
	    				$await	 = !empty($_USER_ACC[$uid]['balance']) ? numberFormat($_USER_ACC[$uid]['await']) : 0;
	    				//用户总资金
	    				$amount	 = !empty($_USER_ACC[$uid]['balance']) ? numberFormat($_USER_ACC[$uid]['amount']) : 0;
	    			}
		    	}
    			
	    		//缓存所以接口返回用户的资金数据
	    		$this->cache->memcached->save($cacheName, gzcompress(serialize($request), 9), 10);
    		}
    	}
    	
    	//在自己之后的用户[不含本人]
    	if (isset($rank_time)) {
    		$rule = array(
				'in'	 => array('status' => array(1, 2)),
				'scope'  => array('lt' => array('rank_time' => $rank_time))
			);
    	}
    	
		if (!$i) {
			//自己的资金属于投标范围
			$diff = 0;
			if (!empty($_USER_ACC[$uid]) && !empty($autobid) && $autobid['status'] == 1
				&& $autobid['tender_money_min'] <= $_USER_ACC[$uid]['balance']) {
				$diff += $autobid['tender_money_max'] < $_USER_ACC[$uid]['balance'] 
					? $autobid['tender_money_max'] 
					: $_USER_ACC[$uid]['balance'];
			}
			
			$data = array(
				'expire'		=> time(),						//读取数据起始时间戳
				'rank' 			=> count($result),				//用户开启自动投标排名
				'rankSum' 		=> count($result),				//所有开启自动投标用户排队总人数
				'fundBefore' 	=> bcsub($fundBefore, $diff),	//排在该用户之前的排队资金
				'fundAmount' 	=> $fundBefore,					//排队总资金
				'borrow'		=> array(
					'one' 	 => $one,		//正在排队用户设置的投一月标数量
					'two' 	 => $two,		//正在排队用户设置的投二月标数量
					'three'  => $three,		//正在排队用户设置的投三月标数量
					'pawn' 	 => $pawn,		//正在排队用户设置的投抵押标数量
					'any' 	 => $any		//正在排队用户设置的投不限标数量
				),
				'autobid'		=> $autobid,	//读取用户设置的自动投标数据
			);
		} else {
			$data['rankSum'] 	+= count($result);
			$data['fundAmount'] += $fundBefore;
			$data['borrow'] = array(
				'one' 	 => $one	,
				'two' 	 => $two	,
				'three'  => $three	,
				'pawn' 	 => $pawn	,
				'any' 	 => $any	,
			);
		}
		//用户总资金、用户可用资金、用户总待收资金
		if (isset($_USER_ACC[$uid])) {
			$data['amount']  = $_USER_ACC[$uid]['amount'];
			$data['balance'] = $_USER_ACC[$uid]['balance'];
			$data['await'] 	 = $_USER_ACC[$uid]['await'];
		}
		
		//对新近用户或关闭自动投标者，并且没设置自动投标的用户 排名就行修正
		if ($i) {
			if (empty($data['autobid']) 
				|| (!empty($data['autobid']) && empty($data['autobid']['status']))
					|| !isset($data['balance'])) {
				//请求接口获取用户资金数据 如果用户没设置自动投标 输出默认数据
				$where  = array('uid' => $uid);
	            $opm    = 'Acc';
	            $request= $this->api_fund->getRow($where, $opm);
				
				$data['amount'] 	= isset($request['data']['amount']) ? $request['data']['amount'] : 0;
				$data['balance'] 	= isset($request['data']['balance']) ? $request['data']['balance'] : 0;
				$data['await'] 		= isset($request['data']['await']) ? $request['data']['await'] : 0;
				$data['rank'] 		= 0;
				$data['fundBefore'] = $data['fundAmount'];
			}
			
			//存入session 用于全局调用
			$_SESSION['complex'] = $data;
		}
		
		$i++;
    	
    	return $i > 1 ? $data : $this->getAutobid($uid, $expire, $rule, $data, $i);
    }
}