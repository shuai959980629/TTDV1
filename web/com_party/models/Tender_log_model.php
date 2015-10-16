<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tender_log_model extends Base_model {
	public $status = array(
        'pendding' => '已投待审',
        'going' => '还款中',
        'early' => '提前结标/已转让',
        'done' => '完成',
        'transfer_success' => '转让成功',
    );

    public function __construct()
    {
        parent::__construct('tender_log');
    }

    /**
     * 投标写数据
     * @param  integer $uid  用户ID
     * @param  integer $bid  借款信息ID
     * @param  integer $amount  投资金额
     * @param  integer $is_auto  是否自动
     * @return array
     */
    private function write($uid, $borrow, $amount, $auto_rank='0', $bag_id=0){
        $this->load->model('borrow_model', 'borrow');
        $this->load->model('tender_log_model', 'tender_log');

        $this->db->trans_begin();
        //加行锁
        $this->db->query(" SELECT * FROM t_borrow WHERE id={$borrow['id']} FOR UPDATE");

        //生成投标记录
        $data['uid'] = $uid;
        $data['borrower'] = $borrow['uid'];
        $data['capital'] = $amount;
        $data['borrow_id'] = $borrow['id'];
        $data['bag_id'] = $bag_id;
        if($auto_rank > 0){
            $data['is_auto'] = 1;
            $data['auto_rank'] = $auto_rank;
        }
        $data['status'] = 'pendding';
        $data['borrow_rank'] = $borrow['tender_times'] + 1;

        $re_tender = $this->tender_log->create($data);
        if(!$re_tender){
            $this->db->trans_rollback();
            $re['status'] = 0;
            $re['msg'] = "投资记录生成失败";
            return $re;
        }

        //3.修改借款信息
        $b_data['load_money'] = $borrow['load_money'] + $amount;
        $b_data['wait_money'] = $borrow['wait_money'] - $amount;
        $b_data['fill_scale'] = round($b_data['load_money'] / $borrow['amount'], 2) * 100;
        $b_data['tender_times'] = $borrow['tender_times'] + 1;
        if($b_data['wait_money'] == 0){
            $b_data['status'] = 'fills';
            $b_data['tender_last_time'] = time();
        }
        $b_where['id'] = $borrow['id'];
        $b_where['wait_money'] = $borrow['wait_money'];
        $re_borrow = $this->borrow->safe_update($b_where, $b_data);
        if(!$re_borrow){
            $this->db->trans_rollback();
            $re['status'] = 0;
            $re['msg'] = "修改借款信息失败";
            return $re;
        }
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            $re['status'] = 0;
            $re['msg'] = "投资事务失败";
            return $re;
        }
        else
        {
            //重新计算用户资金，冻结资金，生成资金流水,调用失败就回滚
            $this->load->model('api_fund_model', 'api_fund');
            $param = array(
                'uid'			=> $uid,
                'money'	    => $amount,
                'tob'			=> 'tender_frozen',
                'rel_data_id'	=> $re_tender,
                'trans_id'		=> $re_tender,
                'pot'			=> date('Ymd'),
            );
            $api = $this->api_fund->send($param);
            if($api['error'] != 1){
                $this->db->trans_rollback();
                $re['status'] = 0;
                $re['msg'] = "资金流水接口调用失败";
                return $re;
            }

            $this->config->load('account_status');
            $account_status = $this->config->item('account_status');
            $account_log = array(
                'rel_data'      => array(
                    'money'=>$amount,
                    'title'=>$borrow['title'],
                    'account'=>$api['data']['balance'],
                    'logs'=>array(
                        0 => array(
                            'status'=>'资金冻结（投资中）',
                            'success'=>1,
                            'created'=>date("Y-m-d H:i:s")
                        ),
                        1 => array(
                            'status'=>'资金解冻',
                            'success'=>0,
                            'created'=>''
                        ),
                    )
                ),
                'rel_type'      => 'tender',
                'uid'           => $uid,
                'ticket_id'     => $re_tender,
            );
            Event::trigger('user_account_change', $account_log);

            $this->db->trans_commit();
        }
        $re['status'] = 1;
        $re['msg'] = "投资成功";
        return $re;
    }

    /**
     * 自动满标（当可投金额小于最小投资金额时）
     * @param  integer $uid  用户ID
     * @param  integer $bid  借款信息ID
     * @param  integer $amount  投资金额
     * @param  integer $is_auto  是否自动
     * @return array
     */
    private function auto_fills($borrow, $amount){
        if(($borrow['status'] != 'verified' && $borrow['status'] != 'auto_lock') || $borrow['tender_money_min'] <= $amount){
            $re['status'] = 0;
            $re['msg'] = "验证失败";
            return $re;
        }

        $re = $this->write(1, $borrow, $amount);
        return $re;
    }

    public function invest($uid, $bid, $amount, $auto_rank='0', $add_apr=0){
        $this->load->model('borrow_model', 'borrow');
        $this->load->model('tender_log_model', 'tender_log');

        $borrow = $this->borrow->get($bid);
        if($borrow['status'] != 'verified' && $borrow['status'] != 'auto_lock'){
            $re['status'] = 0;
            $re['msg'] = "标状态错误：".$borrow['status'];
            return $re;
        }
        if($borrow['wait_money'] < $amount){
            $re['status'] = 0;
            $re['msg'] = "待投金额小于投资金额 wait_money:".$borrow['wait_money'].' 投资金额:'.$amount;
            return $re;
        }
        if($borrow['tender_money_min'] > $amount || $borrow['tender_money_max'] < $amount){
            $re['status'] = 0;
            $re['msg'] = "金额不满足标设置 tender_money_min：".$borrow['tender_money_min']." tender_money_max:".$borrow['tender_money_max'].' 投资金额:'.$amount;
            return $re;
        }
        if(($borrow['verified_time'] + $borrow['end_time']) < time()){
            $re['status'] = 0;
            $re['msg'] = "已过投标有效期";
            return $re;
        }
        $re = $this->write($uid, $borrow, $amount, $auto_rank, $add_apr);
        if($re['status'] == 1){
            $wait_money = $borrow['wait_money'] - $amount;
            if($borrow['tender_money_min'] > $wait_money && $wait_money != 0){
                $borrow = $borrow = $this->borrow->get($bid);
                $this->auto_fills($borrow, $wait_money);
            }
            if($borrow['tender_money_min'] > $wait_money){
                $tender = $this->all(array('borrow_id'=>$borrow['id']));
                foreach($tender as $v){
                    $account_log = array(
                        'rel_data'      => array(
                            'money'=>$v['capital'],
                            'title'=>$borrow['title'],
                            'logs'=>array(
                                0 => array(
                                    'status'=>'资金冻结（审核中）',
                                    'success'=>1,
                                    'created'=>date("Y-m-d H:i:s")
                                ),
                            )
                        ),
                        'rel_type'      => 'tender',
                        'uid'           => $v['uid'],
                        'ticket_id'     => $v['id'],
                    );
                    Event::trigger('user_account_change', $account_log);
                }
            }
        }

        return $re;
    }

    /**
     * 根据借款ID获取投资记录列表
     * @param  integer  $bid  借款信息ID
     * @return array
     */
    public function get_by_borrow($bid, $user=''){
        $this->load->model('plus_coupons_model', 'plus_coupons');

        $data = $this->all(array('borrow_id'=>$bid, 'order_by'=>'id asc'));
        if($data){
            $this->load->model('user_identity_model', 'user_identity');
            $this->load->model('user_info_model', 'user_info');
            foreach($data as $k=>$v){
                if($v['bag_id'] > 0){
                    $bag = $this->plus_coupons->get_Ones($v['bag_id']);
                    $data[$k]['add_apr'] = $bag['apr'];
                }
                if($user == 'nickname'){
                    $user_info = $this->user_info->get($v['uid']);
                    $data[$k]['nickname'] = $user_info['nickname'];
                }else{
                    $user_identity = $this->user_identity->getIdentityByUid($v['uid']);
                    $data[$k]['realname'] = $user_identity['realname'];
                }
            }
        }

        return $data;
    }

    /**
     * 总待收、总待收本金、总待收利息
     * @return array
     */
    public function get_all_wait(){
        $re = $this->sum(array('recover_wait_money', 'recover_wait_interest', 'recover_wait_capital'), array('status'=>'going'));
        return $re[0];
    }
    /**
     * 总待收、总待收本金、总待收利息
     * @return
     */
    public function get_repay_interest(){
        $this->db->where_in('status', array('going','early','done','transfer_success'));
        $this->db->select_sum('repay_total_interest');
        $query = $this->db->get($this->table);
        $re = $query->result_array();
        return $re[0]['repay_total_interest'];
    }

    /**
     * 获取某个标的总利息
     * @return
     */
    public function get_ones_interest($borrow_id, $field)
    {
        $re = $this->sum($field, array('borrow_id'=>$borrow_id));
        return $re;
    }
    /**
     * 获取某个用户的投资总额（特权金除外）
     * @return
     */
    public function get_tender_by_uid($uid)
    {
        $this->load->model('borrow_model', 'borrow');
        $where = array(
            'sum'=>'capital',
        );
        $all_try = $this->borrow->all(array('member_flag'=>'try'), array('id'));
        if($all_try){
            $borrow_id = array();
            foreach($all_try as $v){
                $borrow_id[] = $v['id'];
            }
            $where['not_in'] = array('borrow_id'=>$borrow_id);
        }
        $this->dbWhere($where);
        $this->db->where(array('uid'=>$uid));
        $query = $this->db->get($this->table);
        $result = $query->result_array();
        return $result[0]['capital'] ? $result[0]['capital'] : 0;
    }
    
    /**
     * 读取单条数据
     *
     * @param  integer | array $mix	//唯一ID 或者 查询条件数组
     * @access public
     * @author LEE
     * @return array
     */
    public function getRow($mix)
    {
    	if (is_array($mix)) {
    		$this->dbWhere($mix);
    		$this->db->where($mix);
			$query = $this->db->get($this->table);
    	} else {
	        $query = $this->db->get_where($this->table, array('id' => $mix));
    	}
    	
    	return $query->row_array();
    }
    
    /**
     * 修改数据
     *
     * @param  array $data	//修改数据
     * @param  array $where	//修改条件
     * @access public
     * @author LEE
     * @return boolean
     */
    public function modify($data, $where)
    {
    	$this->db->where($where);
		return $this->db->update($this->table, $data) ? true : false;
    }
    
    /**
     * 特殊条件组合查询
     *
     * @param  array $where	//特殊查询条件
     * @access private
     * @author LEE
     * @return void
     */
    private function dbWhere(&$where)
    {
    	if (!empty($where['cols'])) {
			$this->db->select(implode($where['cols'], ','));
			unset($where['cols']);
		}
		
    	if (isset($where['start_time']) && !empty($where['start_time'])) {
            $this->db->where('created >=', $where['start_time']);
            unset($where['start_time']);
        }

        if (isset($where['end_time']) && !empty($where['end_time'])) {
            $this->db->where('created <=', "{$where['end_time']} 23:59:59");
            unset($where['end_time']);
        }
        
        if (!empty($where['scope'])) {
        	foreach ($where['scope'] as $key=>$value) {
        		isset($value['lt']) && $this->db->where($key  . ' > ' , $value['lt']);
        		isset($value['ltt']) && $this->db->where($key . ' >= ', $value['ltt']);
        		isset($value['mt']) && $this->db->where($key  . ' < ' , $value['mt']);
        		isset($value['mtt']) && $this->db->where($key . ' <= ', $value['mtt']);
        	}
        	unset($where['scope']);
        }
        
        if (!empty($where['in'])) {
        	foreach ($where['in'] as $key=>$value) {
        		$this->db->where_in($key, $value);
        	}
        	unset($where['in']);
        }
        
        if (!empty($where['not_in'])) {
        	foreach ($where['not_in'] as $key=>$value) {
        		$this->db->where_not_in($key, $value);
        	}
        	unset($where['not_in']);
        }
        
        if (!empty($where['like'])) {
        	foreach ($where['like'] as $key=>$value) {
				$this->db->like($key, $value[0], $value[1]);
			}
        	unset($where['like']);
        }
        
        if (!empty($where['sum'])) {
        	$this->db->select_sum($where['sum']);
        	unset($where['sum']);
        }
        
        if (!empty($where['order'])) {
        	$order = array();
        	foreach ($where['order'] as $key=>$value) {
        		$order[] = $key . ' ' . $value;
        	}
            $this->db->order_by(implode($order, ','));
            unset($where['order']);
        }
    }
    
    /**
     * 获取多条数据 [分页]
     *
     * @param  array   $where	//查询条件
     * @param  integer $limit	//查询条数
     * @param  integer $offset	//偏移量
     * @access public
     * @author LEE
     * @return array
     */
    public function getRowPages($where, $limit = 10, $offset = 0)
    {
    	$this->dbWhere($where);
        $result = $this->search($where, $limit, $offset);
        return !empty($result) ? $result : array();
    }
    
    /**
     * 获取多条数据 [多条]
     *
     * @param  array $where	//查询条件
     * @access public
     * @author LEE
     * @return array
     */
    public function getRows($where)
    {
    	$this->dbWhere($where);
    	$this->db->where($where);
        $query = $this->db->get($this->table);
        return $query->result_array();
    }
    
    /**
     * 获取数据总数
     *
     * @param  array   $where	//查询条件
     * @access public
     * @author LEE
     * @return integer
     */
    public function getTotal($where)
    {
    	$this->dbWhere($where);
    	return $this->count_all($where);
    }
	 /**
     * 获取条件内总金额
     *
     * @param  array   $where	//查询条件
     * @access public
     * @author LEE
     * @return integer
     */
    public function getSum($field,$where)
    {
		if(!empty($where)){
			$this->dbWhere($where);
		}
		if(empty($field)){
			return array();	
		}
		$result = $this->sum($field,$where);
		return $result[0];
    }
    
    /**
     * 拒绝债权转让申请
     *
     * @param  integer $id //tender_log ID
     * @access public
     * @author LEE
     * @return array
     */
    public function refuse($id)
    {
    	$this->load->model('borrow_model', 'borrow');
    	
    	if (empty($id) || !is_numeric($id)) {
            $error = array(
            	'error' => 0,
            	'msg'	=> '无效的ID'
            );
            return $error;
    	}
    	
    	$data = array(
    		'creditor' => 'failure',
    	);
    	$where = array('id' => $id);
    	if (!$this->modify($data, $where)) {
    		$error = array(
            	'error' => 0,
            	'msg'	=> '操作失败，请重试'
            );
            return $error;
    	}
    	
    	$tender = $this->getRow($id);
    	$borrow = $this->borrow->get($tender['borrow_id']);
    	
    	$this->load->model('api_fund_model', 'api_fund');
    	$where  = array('uid' => $tender['uid']);
        $opm    = 'Acc';
        $useAcc = $this->api_fund->getRow($where, $opm);
        
        $this->config->load('account_status');
        $account_status = $this->config->item('account_status');
    	
    	//写入LOG
		$account_log = array(
			'rel_data' 	=> array(
				'money'		=> $tender['recover_wait_capital'],
				'title'		=> $borrow['title'],
				'account'	=> !empty($useAcc['data']) && !empty($useAcc['data']['balance']) ? $useAcc['data']['balance'] : 0,
				'logs'		=> array(
					'routine_fee'	=> bcsub($tender['recover_wait_capital'], $tender['recover_wait_capital'] * 0.005),
					'serve_fee'		=> $tender['recover_wait_capital'] * 0.005,
					'data'			=> array(
						1 => array(
							'status'  => $account_status['audit_fail'],
							'success' => 1,
							'created' => date("Y-m-d H:i:s")
						),
						2 => array(
							'status'  => $account_status['nassignment_transfer_fail'],
							'success' => 1,
							'created' => date("Y-m-d H:i:s")
						)
					)
				)
			),
			'rel_type'      => 'nassignment',
			'uid'           => $tender['uid'],
			'ticket_id'     => $tender['id'],
		);
		
		Event::trigger('user_account_change', $account_log);
		
		$error = array(
        	'error' => 1,
        	'msg'	=> '操作成功'
        );
        
        return $error;
    }
}
