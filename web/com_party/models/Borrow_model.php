<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Borrow_model extends Base_model {
    public $tender_money_min = 50;
    //债权转让密码有效时间（十分钟）
    public $transfer_lasts = 600;
    public $deal_flag = array(
        'pledge'   => '质押',
        'mortgage' => '抵押',
    );
    public $member_flag = array(
        'normal'   => '不限',
        'novice' => '新手标',
        'try' => '体验标',
    );
    public $repay_type = array(
        'day'   => '天天返息，到期还本',
        'monthly' => '按月返息还本',
        'matured' => '到期还本付息',
    );
    public $interest_type = array(
        'day'   => '天天返息',
        'monthly' => '等额本息',
    );
    /*public $interest_type = array(
        'normal'   => '天天返息',
        'equal_installments_monthly' => '等额本息',
    );*/
    public $period = array(
        '30'   => '一个月',
        '60' => '两个月',
        '90' => '三个月',
        '180' => '六个月',
        '270' => '九个月',
        '360' => '十二个月',
        '540' => '十八个月',
        '7' => '7天',
    );
    public $transfer_period = array(
        '0-29'   => '一月以上',
        '30-89' => '1-3月',
        '90-179' => '3-6月',
        '180-359' => '6-12月',
        '360-' => '12月及以上',
    );
    public $end_time = array(
        '259200'=>'三天后',
        '172800'=>'两天后',
    );
    public $status = array(
        'draft'   => '草稿',
        'pending' => '待审',
        'cancel' => '已取消',
        'published' => '已发布但不可投',
        'verified' => '已初审',
        'failure' => '失败',
        'fills' => '已满标',
        'repayment' => '还款中',
        'done' => '完成',
        'auto_lock' => '自动投标中',
        'early'=>'提前还款',
    );
    //可变数组中值为空值不显示在管理员操作中
    public $status_change = array(
        'draft'   => array('pending'=>'发布待审', 'cancel'=>'取消'),
        'pending' => array('verified'=>'审核通过', 'published'=>'发布但不可投', 'cancel'=>'取消', 'auto_lock'=>''),
        'cancel' => array(),
        'published' => array('verified'=>'审核通过', 'auto_lock'=>''),
        'verified' => array('fills'=>'满标'),
        'failure' => array(),
        'fills' => array('repayment'=>'复审通过'),
        'repayment' => array('early'=>'提前还款', 'done'=>''),
        'done' => array(),
        'auto_lock' => array('fills'=>'', 'verified'=>'审核通过'),
        'early' => array(),
    );
    public $display_status = array(
        'verified' => '立即投资',
        'fills' => '审核中',
        'repayment' => '还款中',
        'done' => '已完成',
        'early' => '提前还款',
        'auto_lock' => '自动投标中',
    );

    public function __construct()
    {
        parent::__construct('borrow');
    }
    /**
     * 修改借款信息状态
     * @param  integer   $id        ID
     * @param  integer   $status    状态值
     * @return boolean
     */
    public function update_status($id, $status){
        $data['status'] = $status;
        if($status == 'verified' || $status == 'auto_lock'){
            $data['verified_time'] = time();
        }
        if($status == 'repayment'){
            $data['reverified_time'] = time();
        }
        $result = $this->update($id, $data);
        if($result){
        	isset($_SESSION['user']) ? admin_log($_SESSION['user']['username']."修改了借款信息状态:将".$id.'状态修改为:'.$this->status[$status]) : '';
            return TRUE;
        }else{
            return FALSE;
        }
    }
    /**
     * 审核借款信息
     * @param  integer   $id        ID
     * @param  integer   $status    状态值
     * @return boolean
     */
	public function verify($id, $status)
    {
		$borrow = $this->get($id);
        if(!array_key_exists($status, $this->status_change[$borrow['status']])){
            return FALSE;
        }

		if($status == 'auto_lock' && $borrow['is_auto']){
            $re = $this->auto_invest($borrow);
		}else if($status == 'repayment'){
            if($borrow['parent_id'] > 0){
                $re = $this->transfer_fills($borrow);
            }else{
                $re = $this->fills($borrow);
            }
		}else if($status == 'failure'){
            $re = $this->failure($borrow);
        }else if($status == 'early'){
            //$re = $this->early($borrow);
        }else{
            $re = $this->update_status($id, $status);
        }
        return $re;
    }
    /**
     * 审核借款信息
     * @param  array   $borrow        借款信息
     * @return boolean
     */
    public function fills($borrow)
    {
        $this->load->model('tender_log_model', 'tender_log');
        $this->load->model('repay_log_model', 'repay_log');
        $this->load->model('api_fund_model', 'api_fund');
        $this->load->model('plus_coupons_model', 'plus_coupons');
        $this->config->load('account_status');
        $account_status = $this->config->item('account_status');

        $tender_log = $this->tender_log->get_by_borrow($borrow['id']);
        if($tender_log){
            foreach($tender_log as $v){
                //如果使用了道具，加息
                if($v['bag_id'] > 0){
                    $this->plus_coupons->sucess_coupons($v['bag_id'], $v['id']);
                    $bag = $this->plus_coupons->get_Ones($v['bag_id']);
                    $add_apr = $bag['apr'];
                }else{
                    $add_apr = 0;
                }
                //调用计算算法计算每期本息
                if($borrow['repay_type'] == 'day'){
                    $equal = EqualDayEnd($borrow['period'], $v['capital'], $borrow['apr'] + $add_apr, "");
                }else if($borrow['repay_type'] == 'monthly'){
                    $equal = EqualMonth($borrow['period'] / 30, $v['capital'], $borrow['apr'] + $add_apr, '', "");
                }

                if($equal){
                    $money = 0;
                    $re = 1;
                    foreach($equal as $key=>$item){
                        $money = $money + $item['account_interest'];
                        if($re){
                            $data['uid'] = $v['uid'];
                            $data['borrow_id'] = $v['borrow_id'];
                            $data['borrower'] = $v['borrower'];
                            $data['tender_id'] = $v['id'];
                            $data['recover_period'] = $key + 1;
                            $data['recover_time'] = $item['repay_time'];
                            $data['recover_amount'] = $item['account_all'];
                            $data['recover_interest'] = $item['account_interest'];
                            $data['recover_capital'] = $item['account_capital'];
                            $data['everyday_interest'] = isset($item['interest_day'])?$item['interest_day']:0;

                            $re = $re && $this->repay_log->replace($data);
                        }else{
                            return FALSE;
                        }
                    }
                }
                if($re){
                    if($borrow['repay_type'] == 'day'){
                        $invest = EqualDayEnd($borrow['period'], $v['capital'], $borrow['apr'] + $add_apr, "all");
                    }else if($borrow['repay_type'] == 'monthly'){
                        $invest = EqualMonth($borrow['period'] / 30, $v['capital'], $borrow['apr'] + $add_apr, '', "all");
                    }
                    $t_data = array(
                        'recover_total_money'=>$invest['account_total'],
                        'recover_total_interest'=>$invest['interest_total'],
                        'recover_wait_money'=>$invest['account_total'],
                        'recover_wait_interest'=>$invest['interest_total'],
                        'recover_wait_capital'=>$invest['capital_total'],
                        'status'=>'going',
                        'recover_times'=>count($equal),
                        'recover_last_time'=>$equal[count($equal) - 1]['repay_time'],
                    );

                    $re = $this->tender_log->update($v['id'], $t_data);
                    if($re === FALSE){
                        return FALSE;
                    }
                    $param = array(
                        'uid'			=> $v['uid'],
                        'money'	    => "{$v['capital']}, {$money}",
                        'tob'			=> 'tender_success_capital,tender_success_interest',
                        'rel_data_id'	=> $v['id'],
                        'trans_id'		=> $v['id'],
                        'pot'			=> date('Ymd'),
                    );
                    $api = $this->api_fund->send($param);
                    if($api['error'] == 1){
                        //记录需要发送的短信
                        $sms_data[$v['id']] = array(
                            'uid'=>$v['uid'],
                            'borrow_no'=>'NO.'.$borrow['sn'],
                            //'time'=>date('Y年m月d日H时i分s秒'),
                            'money'=>round($v['capital'], 0),
                            'income'=>$invest['interest_total'],
                            'repaydate'=>date('Y年m月d日', $t_data['recover_last_time']),
                            'borrow_info'=>($borrow['period'] % 30 == 0 ? ($borrow['period'] / 30).'月' : $borrow['period'].'天')."/".($borrow['deal_flag'] == 'mortgage' ? '抵押' : '质押')."/".$borrow['apr'],
                            'is_auto'=>$v['is_auto'],
                            'tender_id'=>$v['id'],
                        );

                        //统计变化
                        $this->load->model('User_account_count_model','user_account_count');
                        $this->user_account_count->upData(
                            array(
                                'await_count'=>$t_data['recover_wait_interest'],
                                'await_corpus_count'=>$t_data['recover_wait_capital'],
                                'await_count_count'=>1
                                ),$v['uid']);

                        $account_log = array(
                            'rel_data'      => array(
                                'money'=>$v['capital'],
                                'title'=>$borrow['title'],
                                'account'=>$api['data']['balance'],
                                'logs'=>array(
                                    1 => array(
                                        'status'=>'资金解冻',
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

                    }else{
                        return FALSE;
                    }
                }else{
                    return FALSE;
                }
            }
            //总利息
            $total_interest = $this->tender_log->get_ones_interest($borrow['id'], array('recover_wait_interest'));
            //修改标的状态
            $re = $this->update($borrow['id'], array('status'=>'repayment', 'reverified_time'=>time(), 'repay_times'=>count($equal), 'repay_last_time'=>$equal[count($equal) - 1]['repay_time'], 'total_interest'=>$total_interest[0]['recover_wait_interest']));
            if(!$re){
                return FALSE;
            }
            $this->sms_tender_success($sms_data);
        }
        return TRUE;
    }
    /**
     * 债权转让满标审核
     * @param  array   $borrow        借款信息
     * @return boolean
     */
    public function  transfer_fills($borrow){
        $this->load->model('tender_log_model', 'tender_log');
        $this->load->model('repay_log_model', 'repay_log');
        $this->load->model('api_fund_model', 'api_fund');
        $this->load->model('User_account_count_model','user_account_count');

        $this->config->load('fee');
        $transfer_fee = $this->config->item('transfer_fee');
        $this->config->load('account_status');
        $account_status = $this->config->item('account_status');

        $old_tender = $this->tender_log->get($borrow['tender_id']);
        $tender_log = $this->tender_log->all(array('borrow_id'=>$borrow['id']));
        $old_repay_log = $this->repay_log->all(array('order_by'=>'recover_time asc', 'tender_id'=>$borrow['tender_id'], 'status'=>'wait', 'recover_time>'=>time()));
        $repay_day = 30 - (strtotime(date('Y-m-d', $old_repay_log[0]['recover_time'])) - strtotime(date('Y-m-d')))  / 86400;
        if($repay_day > 0){
            //计算转让人当期利息
            $old_repay_interest = bcmul($old_repay_log[0]['recover_interest'] * $repay_day / 30, 1, 3);
            $old_repay_interest_rest =  bcsub($old_repay_log[0]['recover_interest'], $old_repay_interest, 3);
        }

       if($tender_log){
           $new_invest_total = 0;
            foreach($tender_log as $value){
                if($borrow['repay_type'] == 'day'){
                    bcscale(3);
                    $interest = bcmul($old_tender['recover_wait_interest'] * $value['capital'] / $borrow['amount'], 1);
                    $data = array(
                        'uid'=>$value['uid'],
                        'borrow_id'=>$value['borrow_id'],
                        'borrower'=>$value['borrower'],
                        'tender_id'=>$value['id'],
                        'recover_period'=>1,
                        'recover_time'=>$old_tender['recover_last_time'],
                        'recover_amount'=>$value['capital'] + $interest,
                        'recover_interest'=>$interest,
                        'recover_capital'=>$value['capital'],
                        'everyday_interest'=>bcmul($interest / ((strtotime(date('Y-m-d', $old_tender['recover_last_time'])) - strtotime(date('Y-m-d'))) / 86400), 1, 3)
                    );
                    $re = $this->repay_log->replace($data);
                    if($re){
                        $t_data = array(
                            'recover_total_money'=>$value['capital'] + $interest,
                            'recover_total_interest'=>$interest,
                            'recover_wait_money'=>$value['capital'] + $interest,
                            'recover_wait_interest'=>$interest,
                            'recover_wait_capital'=>$value['capital'],
                            'status'=>'going',
                            'recover_times'=>1,
                            'recover_last_time'=>$old_tender['recover_last_time'],
                        );

                        $re = $this->tender_log->update($value['id'], $t_data);
                        if($re === FALSE){
                            return FALSE;
                        }
                    }else{
                        return FALSE;
                    }

                }else if($borrow['repay_type'] == 'monthly'){
                    $equal = EqualMonth(ceil($borrow['period'] / 30), $value['capital'], $borrow['apr'], '', "");

                    $interest = 0;
                    $re = 1;
                    foreach($equal as $key=>$item){
                        //如果是第一期，转让人投资人按天数分当期利息
                        if($key == 0){
                            //该笔投资第一期分的的利息
                            $first_equal_interest = bcmul($old_repay_interest_rest * $value['capital'] / $old_tender['capital'], 1, 3);
                            //根据计息算法第一期少收利息
                            $first_less_interest = bcsub($item['account_interest'], $first_equal_interest, 3);
                            $item['account_all'] = bcsub($item['account_all'], $first_less_interest, 3);
                            $item['account_interest'] = $first_equal_interest;
                        }
                        $interest = $interest + $item['account_interest'];
                        if($re){
                            $data['uid'] = $value['uid'];
                            $data['borrow_id'] = $value['borrow_id'];
                            $data['borrower'] = $value['borrower'];
                            $data['tender_id'] = $value['id'];
                            $data['recover_period'] = $key + 1;
                            $data['recover_time'] = $old_repay_log[$key]['recover_time'];
                            $data['recover_amount'] = $item['account_all'];
                            $data['recover_interest'] = $item['account_interest'];
                            $data['recover_capital'] = $item['account_capital'];

                            $re = $re && $this->repay_log->replace($data);
                        }else{
                            return FALSE;
                        }
                    }
                    if($re){
                        $invest_total = EqualMonth(ceil($borrow['period'] / 30), $value['capital'], $borrow['apr'], '', "all");
                        $t_data = array(
                            'recover_total_money'=>bcsub($invest_total['account_total'], $first_less_interest, 3),
                            'recover_total_interest'=>bcsub($invest_total['interest_total'], $first_less_interest, 3),
                            'recover_wait_money'=>bcsub($invest_total['account_total'], $first_less_interest, 3),
                            'recover_wait_interest'=>bcsub($invest_total['interest_total'], $first_less_interest, 3),
                            'recover_wait_capital'=>$invest_total['capital_total'],
                            'status'=>'going',
                            'recover_times'=>count($equal),
                            'recover_last_time'=>$equal[count($equal) - 1]['repay_time'],
                        );

                        $re = $this->tender_log->update($value['id'], $t_data);
                    }
                }

                $new_invest_total += $interest;

                if($re === FALSE){
                    return FALSE;
                }else{
                    $param = array(
                        'uid'			=> $value['uid'],
                        'money'	    => "{$value['capital']}, {$interest}",
                        'tob'			=> 'tender_success_capital,tender_success_interest',
                        'rel_data_id'	=> $value['id'],
                        'trans_id'		=> $value['id'],
                        'pot'			=> date('Ymd'),
                    );
                    $api = $this->api_fund->send($param);
                    if($api['error'] == 1){
                        //统计变化
                        $this->user_account_count->upData(
                            array(
                                'await_count'=>$t_data['recover_wait_interest'],
                                'await_corpus_count'=>$t_data['recover_wait_capital'],
                                'await_count_count'=>1
                            ),$value['uid']);

                        $account_log = array(
                            'rel_data'      => array(
                                'money'=>$value['capital'],
                                'title'=>$borrow['title'],
                                'account'=>$api['data']['balance'],
                                'logs'=>array(
                                    1 => array(
                                        'status'=>'资金解冻',
                                        'success'=>1,
                                        'created'=>date("Y-m-d H:i:s")
                                    ),
                                )
                            ),
                            'rel_type'      => 'tender',
                            'uid'           => $value['uid'],
                            'ticket_id'     => $value['id'],
                        );
                        Event::trigger('user_account_change', $account_log);

                    }else{
                        return FALSE;
                    }
                }
            }
            //修改原投资记录相关数据
            $fee = round(floor($old_tender['recover_wait_capital']) * $transfer_fee['transfer_fee'], 3);
            $param_money = "{$old_tender['recover_wait_capital']},{$old_tender['recover_wait_interest']},{$fee}";
            $param_tob = 'assignment_charge,assignment_interest,assignment_fee';
            if(isset($old_repay_interest) && $old_repay_interest > 0){
                $param_money .= ",{$old_repay_interest}";
                $param_tob .= ',assignment_repay_interest';
            }
            $param = array(
                'uid'			=> $old_tender['uid'],
                'money'	    => $param_money,
                'tob'			=> $param_tob,
                'rel_data_id'	=> $old_tender['id'],
                'trans_id'		=> $old_tender['id'],
                'pot'			=> date('Ymd'),
            );
            $api = $this->api_fund->send($param);
            if($api['error'] != 1){
                return FALSE;
            }

            //统计变化
            $this->user_account_count->upData(
                array(
                    'await_count'=>-$old_tender['recover_wait_interest'],
                    'await_corpus_count'=>-$old_tender['recover_wait_capital'],
                    'await_count_count'=>1
                ),$old_tender['uid']);

            //写入LOG
            $account_log = array(
                'rel_data' 	=> array(
                    'money'=>$old_tender['recover_wait_capital'],
                    'title'=>$borrow['title'],
                    'account'=>$api['data']['balance'],
                    'logs'		=> array(
                        'routine_fee'=>$old_tender['capital'],
                        'serve_fee'=>$fee,
                        'repay_interest'=>$old_repay_interest,
                        'data'			=> array(
                            2 => array(
                                'status'  => $account_status['transfer_success'],
                                'success' => 1,
                                'created' => date("Y-m-d H:i:s")
                            ),
                        )
                    )
                ),
                'rel_type'      => 'nassignment',
                'uid'           => $old_tender['uid'],
                'ticket_id'     => $old_tender['id'],
            );
            Event::trigger('user_account_change', $account_log);

            $re = $this->repay_log->modify(array('status'=>'ended'), array('tender_id'=>$borrow['tender_id'], 'recover_time>'=>time()));
            if(!$re){
                return FALSE;
            }
            $old_t_data = array(
                'status'=>'transfer_success',
                'creditor'=>'success',
                'transfer_diff'=>$old_tender['recover_wait_interest'] - $old_repay_interest - $new_invest_total,
                'repay_total_money'=>bcadd(bcadd($old_tender['repay_total_money'], $old_repay_interest, 3), $old_tender['recover_wait_capital'], 3),
                'repay_total_interest'=>bcadd($old_tender['repay_total_interest'], $old_repay_interest, 3),
                'repay_total_capital'=>$old_tender['capital'],
                'recover_wait_money'=>0,
                'recover_wait_interest'=>0,
                'recover_wait_capital'=>0,
            );
            $re = $this->tender_log->update($borrow['tender_id'], $old_t_data);
            if($re === FALSE){
                return FALSE;
            }

           //总利息
           $total_interest = $this->tender_log->get_ones_interest($borrow['id'], array('recover_wait_interest'));
           //修改标的状态
           $re = $this->update($borrow['id'], array('status'=>'repayment', 'reverified_time'=>time(), 'repay_times'=>isset($equal)?count($equal):1, 'total_interest'=>$total_interest[0]['recover_wait_interest']));

           if($re === FALSE){
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * 自动投标
     * @param  array   $borrow        借款信息
     * @return boolean
     */
    public function auto_invest($borrow)
    {
        $this->load->model('tender_auto_seting_model', 'tender_auto_seting');
        $this->load->model('tender_log_model', 'tender_log');
        $this->load->model('api_fund_model', 'api_fund');
        $this->load->model('plus_coupons_model', 'plus_coupons');
        //将借款信息改为锁定状态
        $this->update_status($borrow['id'], 'auto_lock');

        $match_user = $this->tender_auto_seting->get_match_user($borrow);
        $is_ok = FALSE;
        //$offset = 0;
        $wait_money = $borrow['wait_money'];
        //while($match_user && !$is_ok){
            foreach($match_user as $k=>$v){
                if($v['tender_money_min'] <= $wait_money){
                    $where  = array('uid' => $v['uid']);
                    $opm    = 'Acc';
                    $user_account = $this->api_fund->getRow($where, $opm);
                     
                    if($user_account['error'] == 1){
                        $user_money = floor($user_account['data']['balance']);
                        //每个标每人最大投资金额
                        //$tender_sum = $this->tender_log->sum(array('capital'), array('borrow_id'=>$borrow['id'], 'uid'=>$v['uid']));
                        //$tender_money_max = $tender_sum[0]['capital'] > 0 ?  $borrow['tender_money_max'] - $tender_sum[0]['capital'] : $borrow['tender_money_max'];
                        $tender_money_max = $borrow['tender_money_max'];
                        //如果用户账户可投金额、此标代投金额、用户设置的最大投资金额、每个标每人最大投资金额的最小值大于等于标的最小投资金额（默认50）则进行投标
                        $money = min($user_money, $wait_money, $v['tender_money_max'], $tender_money_max);
                        if($money >= $v['tender_money_min']){
                            $coupons = 0;
                            if(substr($borrow['add_apr'], 1, -1) && isset($v['coupons']) && $v['coupons'] > 0){
                                if($re = $this->plus_coupons->use_plus($v['coupons'], $v['uid'], substr($borrow['add_apr'], 1, -1))){
                                    $coupons = $v['coupons'];
                                }
                            }

                            $invest = $this->tender_log->invest($v['uid'], $borrow['id'], $money, $k+1, $coupons);
                            if($invest['status']){
                                //投资成功更新排名时间，如果使用了加息券，更新加息券字段
                                $tas_data = array('rank_time'=>rank_time());
                                if($coupons > 0){
                                    $tas_data['coupons'] = 0;
                                }
                                $this->tender_auto_seting->update($v['uid'], $tas_data);
                                $wait_money = $wait_money - $money;
                            }else{
                                log_message('error', "uid:{$v['uid']},{$invest['msg']},user_money:{$user_money}", "invest_failed");
                                continue;
                            }
                        }else{
                            log_message('error', "uid:{$v['uid']},money:".$money." tender_money_min:".$v['tender_money_min'], "invest_failed");
                            continue;
                        }
                    }else{
                        log_message('error', "uid:{$v['uid']},api error:".json_encode($user_account), "invest_failed");
                        continue;
                    }
                    if($wait_money < $borrow['tender_money_min']){
                        $is_ok = TRUE;
                        break;
                    }
                }else{
                    continue;
                }
            }
            /*if(!$is_ok){
                $offset = $offset + count($match_user);
                $match_user = $this->tender_auto_seting->get_match_user($borrow, $offset);
            }*/
        //}

        //修改借款信息状态
        if(!$is_ok){
            return $this->update_status($borrow['id'], 'verified');
        }

        return TRUE;
    }
    /**
     * 审核投资失败
     * @param  array   $borrow        借款信息
     * @return boolean
     */
    public function failure($borrow)
    {
        $this->load->model('tender_log_model', 'tender_log');
        $this->load->model('api_fund_model', 'api_fund');
        
        //修改标状态
    	$this->update($borrow['id'], array('status' => 'failure'));
    	
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
    	
    	if ($borrow['uid'] > 0) {
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
                        'serve_fee'=>0,
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
    	}
    	
    	return true;
    }
    
    /**
     * 提前结标
     * @param  array   $borrow        借款信息
     * @return boolean
     */
    public function early($borrow)
    {
        if($borrow['status'] != 'repayment'){
            return FALSE;
        }

        $this->load->model('repay_log_model', 'repay_log');
        $this->load->model('tender_log_model', 'tender_log');
        $this->load->model('api_fund_model', 'api_fund');
        $this->load->model('user_account_log_model', 'user_account_log');
        $this->load->model('award_model', 'award');
        $early_borrow_apr = bcdiv($borrow['early_apr'], 1000, 4);
        $account_status = $this->config->load('account_status');

        //修改所有还款
        $re = $this->repay_log->modify(array('status'=>'early'), array('borrow_id'=>$borrow['id'], 'status'=>'wait'));
        if(!$re){
            return FALSE;
        }

        $tender_log = $this->tender_log->all(array('borrow_id'=>$borrow['id'], 'status'=>'going'));

        if($tender_log){
            foreach($tender_log as $v){
                $r_where = array(
                    'tender_id'=>$v['id'],
                    'order_by'=>'recover_time asc',
                    'recover_time>'=>time(),
                );
                $rest_repay = $this->repay_log->search($r_where);
                if(!$rest_repay){
                    return FALSE;
                }
                //计算需要提前返还的利息（天天返：如果是最后一月则为剩余代收利息，否则按当月剩余天数*每天返利息计算；等额本息则为当期代收利息）
                if($borrow['repay_type'] == 'day'){
                    if(count($rest_repay) == 1){
                        $bc_interest = $v['recover_wait_interest'];
                    }else{
                        //未返天数
                        $days = (strtotime(date('Y-m-d', $v['recover_last_time'])) - strtotime(date('Y-m-d'))) / 86400;
                        $bc_days = $days % 30;
                        $bc_interest = bcmul($rest_repay[0]['everyday_interest'], $bc_days, 3);
                    }
                }else if($borrow['repay_type'] == 'monthly'){
                    $bc_interest = $rest_repay[0]['recover_interest'];
                }

                //提前返息更新tender_log
                $t_data = array(
                    'repay_total_money' => bcadd($v['capital'], bcadd($v['repay_total_money'], $bc_interest, 3), 3),
                    'repay_total_interest' => bcadd($v['repay_total_interest'], $bc_interest, 3),
                    'repay_total_capital' => $v['capital'],
                    'recover_wait_money' => 0,
                    'recover_wait_interest' =>  0,
                    'recover_wait_capital' => 0,
                    'status' => 'early',
                );
                $re = $this->tender_log->update($v['id'], $t_data);
                if(!$re){
                    return FALSE;
                }

                //提前返还剩余本金
                $param = array(
                    'uid'			=> $v['uid'],
                    'money'	    	=> $v['recover_wait_capital'].','.$v['recover_wait_interest'],
                    'tob'			=> 'borrow_repay,borrow_repay_interest',
                    'rel_data_id'	=> $v['id'],
                    'trans_id'		=> $rest_repay[0]['id'].'_'.date('Ymd'),
                    'pot'			=> date('Ymd'),
                );

                $api = $this->api_fund->send($param);
                if($api['error'] != 1){
                    return FALSE;
                }
                //生成还本流水
                $account_log = array(
                    'rel_data'      => array(
                        'money'=>$v['recover_wait_capital'],
                        'title'=>$borrow['title'],
                        'account'=>$api['data']['balance'],
                    ),
                    'rel_type'      => 'repayment',
                    'uid'           => $v['uid'],
                    'ticket_id'     => $rest_repay[0]['id'],
                    'created'=>date('Y-m-d H:i:s'),
                );
                $this->user_account_log->write($account_log);

                //通过奖励提前反当期利息
                $award_data = array(
                    'uid'=>$v['uid'],
                    'activity'=>'early_interest',
                    'money'=>$bc_interest,
                    'remark'=>'NO.'.$borrow['sn'].'提前返息',
                    'status'=>0,
                );
                $re = $this->award->createAward($award_data);
                if(!$re){
                    return FALSE;
                }

                $award_data = array(
                    'uid'=>$v['uid'],
                    'activity'=>'early_borrow',
                    'money'=>bcmul($v['recover_wait_capital'], $early_borrow_apr, 3),
                    'remark'=>'NO.'.$borrow['sn'].'提前还款补偿',
                    'status'=>0,
                );
                $re = $this->award->createAward($award_data);
                if(!$re){
                    return FALSE;
                }
                $sms_data[$v['id']] = array(
                    'uid'=>$v['uid'],
                    'borrow_no'=>'NO.'.$borrow['sn'],
                    //'time'=>date('Y年m月d日H时i分s秒'),
                    'money'=>$v['recover_wait_capital'],
                    'apr'=>$borrow['early_apr'],
                    'income'=>$bc_interest,
                    'borrow_info'=>($borrow['period'] % 30 == 0 ? ($borrow['period'] / 30).'月' : $borrow['period'].'天')."/".($borrow['deal_flag'] == 'mortgage' ? '抵押' : '质押')."/".$borrow['apr'],
                );
                if($v['creditor'] == 'verify'){
                    $this->tender_log->refuse($v['id']);
                }
            }

            $this->sms_early_borrow($sms_data);
        }
        //已还总利息
        $total_interest = $this->tender_log->get_ones_interest($borrow['id'], array('repay_total_interest'));
        //修改标的状态
        $re = $this->update($borrow['id'], array('status'=>'early', 'total_interest'=>$total_interest[0]['repay_total_interest']));
        if(!$re){
            return FALSE;
        }
        //检查此标是否有转让记录（转让成功递归调用此方法，投资中满标中则流标，申请中则审核不通过）
        $transfer = $this->all(array('uid>'=>0, 'parent_id'=>$borrow['id']));
        if($transfer){
            foreach($transfer as $v){
                if($v['status'] == 'repayment'){
                    $this->update($v['id'], array('early_apr'=>$borrow['early_apr'], 'repay_last_time'=>time()));
                    $v['early_apr'] = $borrow['early_apr'];
                    $this->early($v);
                }
                
                elseif (in_array($v['status'], array('fills', 'verified'))) {
                	$this->failure($v);
                }
            }
        }

        return TRUE;
    }
    /**
     * 生成借款信息sn
     * @return boolean
     */
    public function get_sn()
    {
        $sn =  date("ymdHis");
        $rand = mt_rand(1,99);

        return sprintf('%s%02s', $sn, $rand);
    }
    /**
     * 今日总成交
     * @return boolean
     */
    public function today_amount()
    {
        $where = array(
            'in' => array('status'=>array('verified','fills','repayment','early','done')),
            'verified_time>' => strtotime(date('Y-m-d')),
            'sum' => 'amount',
        );
        $today_borrow = $this->getRow($where);
        return $today_borrow['amount'] ? $today_borrow['amount'] : 0;
    }

	public function _where($where){
		if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
            unset($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->table}.{$this->pk} desc");
        }
		/*
		if (isset($where['title']) && !empty($where['title'])) {
            $this->db->like('title', $where['title']);
            unset($where['title']);
        }*/
		//指定查询字段 LEE+
		if (!empty($where['cols'])) {
			$this->db->select(implode($where['cols'], ','));
			unset($where['cols']);
		}
		//like查询 LEE+
		if (!empty($where['like'])) {
			foreach ($where['like'] as $key=>$value) {
				$this->db->like($key, $value[0], $value[1]);
			}
        	unset($where['like']);
		}
		//指派查询字段 LEE+
		if (!empty($where['cols'])) {
			$this->db->select(implode($where['cols'], ','));
			unset($where['cols']);
		}
		//in查询 LEE+
		if (!empty($where['in'])) {
        	foreach ($where['in'] as $key=>$value) {
        		$this->db->where_in($key, $value);
        	}
        	unset($where['in']);
        }
        //查询取值范围 LEE+
        if (!empty($where['scope'])) {
        	foreach ($where['scope'] as $key=>$value) {
        		isset($value['ltt']) && $this->db->where($key . ' >= ', $value['ltt']);
        		isset($value['mtt']) && $this->db->where($key . ' <= ', $value['mtt']);
        	}
        	unset($where['scope']);
        }
        //sum查询 LEE+
        if (!empty($where['sum'])) {
        	$this->db->select_sum($where['sum']);
        	unset($where['sum']);
        }
		
		if (!empty($where['start_time'])) {
            $this->db->where('created >', date('Y-m-d', strtotime($where['start_time'])));
            unset($where['start_time']);
        }
		if (!empty($where['end_time'])) {
            $this->db->where('created <', date('Y-m-d', strtotime($where['end_time'])). '23:59:59');
            unset($where['end_time']);
        }

        $this->db->where($where);
	}

    /**
     * 根据条件更新数据
     * @return
     */
    public function safe_update($where, $data)
    {
        $re = $this->db->where($where)
            ->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return $re;
        }
        else {
            return NULL;
        }
    }
    /**
     * 发送投资成功短信
     * @return
     */
    public function sms_tender_success($sms_data){
        $this->load->model('user_model', 'user');

        if(is_array($sms_data) && $sms_data){
            foreach($sms_data as $v){
                $user = $this->user->get($v['uid']);
                $data = $v;
                unset($data['uid']);
                $data['template'] = $v['is_auto'] == 1 ? 'tender_auto_template' : 'tender_hand_template';
                unset($data['is_auto']);
                //发送短信
                $re = send_sms($user['mobile'], $data);var_dump($re);
                log_message('error', json_encode($data).'---'.$re, 'tender_sms');
            }
        }
    }

    /**
     * 发送投资成功短信
     * @return
     */
    public function sms_early_borrow($sms_data){
        $this->sms_batch($sms_data, 'repay_early_template');
    }
    /**
     * 批量发送短信
     * @param $sms_data array	//短信模板对应字段数据(根据uid查询号码)
     * @access string //模板
     * @return
     */
    public function sms_batch($sms_data, $template){
        $this->load->model('user_model', 'user');

        if(is_array($sms_data) && $sms_data){
            foreach($sms_data as $v){
                $user = $this->user->get($v['uid']);
                $data = $v;
                unset($data['uid']);
                $data['template'] = $template;
                //发送短信
                send_sms($user['mobile'], $data);
                log_message('error', json_encode($data), 'tender_sms');
            }
        }
    }

    /**
     * 获取多条数据
     *
     * @param  array   $where	//查询条件
     * @access public
     * @author LEE
     * @return array
     */
    public function getRows($where)
    {
    	$this->_where($where);
        $query = $this->db->get($this->table);
        return $query->result_array();
    }
    
    /**
     * 获取单条数据
     *
     * @param  integer | array $mix	//唯一ID 或者 查询条件数组
     * @access public
     * @author LEE
     * @return array
     */
    public function getRow($mix)
    {
    	if (is_array($mix)) {
    		$this->_where($mix);
			$query = $this->db->get($this->table);
    	} else {
	        $query = $this->db->get_where($this->table, array('id' => $mix));
    	}
    	
        return $query->row_array();
    }
}
