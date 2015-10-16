<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @还款计划明细
 * @还款状态「wait:等待还款，early：提前结标/已转让, done:已还」
 * @copyright(c) 2015-05-18
 */
class Repay_log_model extends Base_model {
    public $status = array(
        'wait' => '等待还款',
        'early' => '提前结标/已转让',
        'done' => '已还',
        'ended' => '已转让',
    );

    public function __construct()
    {
        parent::__construct('repay_log');
    }

    /**
     * @根据借款信息获取当天符合条件的还款信息
     * @param array $borrow  借款信息
     * @return array
     */
    public function get_today_repay($borrow){
        if($borrow['status'] == 'repayment' || $borrow['member_flag'] == 'try' ){
            $where = array('status'=>'wait', 'borrow_id'=>$borrow['id']);
            if($borrow['repay_type'] == 'day'){
                $where['recover_time >= '] = strtotime(date('Y-m-d'));
            }else if($borrow['repay_type'] == 'monthly'){
                $where['recover_time <='] = strtotime(date('Y-m-d').' 23:59:59');
                $where['recover_time >='] = strtotime(date('Y-m-d'));
            }
            return $this->all($where);
        }else{
            return NULL;
        }
    }

    /**
     * @根据借款信息ID还本息
     * @param borrow_id int  借款信息id
     * @return
     */
    public function repay_by_borrow($borrow_id){
        $this->load->model('borrow_model', 'borrow');
        $this->load->model('tender_log_model', 'tender_log');
        $this->load->model('User_account_log_model', 'user_account_log');
        $this->load->model('api_fund_model', 'api_fund');

        $borrow = $this->borrow->get($borrow_id);write_repay_log(json_encode($borrow));
        if($borrow['status'] != 'repayment' && $borrow['member_flag'] != 'try'){
            return true;
        }
        //当日时间戳
        $today  = strtotime(date('Ymd'));
        if ($borrow['reverified_time'] > $today) {
            return true;
        }
        $rep_log = $this->get_today_repay($borrow);write_repay_log($rep_log);
        $write_repay_log = 'borrow_id:'.$borrow_id;
        $sms_data = '';
        if($rep_log){
            foreach($rep_log as $v){
                //如果是特权标，还款记录生成时间要大于当天
                if($borrow['member_flag'] == 'try'){
                    if(strtotime($v['created']) > $today){
                        continue;
                    }
                }
                $money_repayment = 0;
                $write_repay_log .= " \r\n rep_log      :".json_encode($v);
                $tender_log = $this->tender_log->get($v['tender_id']);
                $write_repay_log .= " \r\n tender_log   :".json_encode($tender_log);
                if($tender_log['status'] == 'going'){
                    if($borrow['repay_type'] == 'day'){
                        $tob = 'day_interest';
                        $money = $v['everyday_interest'];
                        $user_account_money = $v['everyday_interest'];
                        //如果是还本的时间
                        if(date('Ymd', $v['recover_time']) == date('Ymd')){
                            $difference_interest = bcsub($v['recover_interest'], bcmul($v['everyday_interest'], $borrow['period'], 3), 3);
                            if($difference_interest > 0){
                                $money = bcadd($money, $difference_interest, 3);
                                $user_account_money = bcadd($user_account_money, $difference_interest, 3);
                            }
                            $tob .= ',repayment';
                            $money .= ','.$v['recover_capital'];
                            $r_data = array(
                                'status'=>'done',
                            );
                            $money_repayment = $v['recover_capital'];
                            $t_data = array(
                                'repay_total_money' => $tender_log['recover_total_money'],
                                'repay_total_interest' => $tender_log['recover_total_interest'],
                                'repay_total_capital' => $tender_log['capital'],
                                'recover_wait_money' => 0,
                                'recover_wait_interest' =>  0,
                                'recover_wait_capital' => 0,
                                'status'=>'done',
                            );
                        }else{
                            //已经天天反息的天数
                            $days = $borrow['period'] - (strtotime(date('Y-m-d', $tender_log['recover_last_time'])) - strtotime(date('Y-m-d'))) / 86400;

                            $t_data = array(
                                'repay_total_money' => bcmul($v['everyday_interest'], $days, 3),
                                'repay_total_interest' => bcmul($v['everyday_interest'], $days, 3),
                                'repay_total_capital' => 0,
                                'recover_wait_money' => bcadd(bcsub($tender_log['recover_total_interest'], bcmul($v['everyday_interest'], $days, 3), 3), $tender_log['recover_wait_capital'], 3),
                                'recover_wait_interest' =>  bcsub($tender_log['recover_total_interest'], bcmul($v['everyday_interest'], $days, 3), 3),
                                'recover_wait_capital' => $tender_log['recover_wait_capital'],
                            );
                        }
                    }else if($borrow['repay_type'] == 'monthly'){
                        $tob = 'repayment';
                        $money_repayment = $v['recover_amount'];
                        $money = $v['recover_amount'];

                        $r_data = array(
                            'status'=>'done',
                        );
                        //计算等额本息未还本金与利息
                        $sum  = $this->sum(array('recover_capital', 'recover_interest'), array('tender_id'=>$v['tender_id'], 'recover_time>'=>strtotime(date('Y-m-d').' 23:59:59')));
                        $t_data = array(
                            'repay_total_money' => bcsub(bcsub($tender_log['recover_total_money'], $sum[0]['recover_interest'], 3), $sum[0]['recover_capital'], 3),
                            'repay_total_interest' => bcsub($tender_log['recover_total_interest'], $sum[0]['recover_interest'], 3),
                            'repay_total_capital' => bcsub(bcsub($tender_log['recover_total_money'], $tender_log['recover_total_interest'], 3), $sum[0]['recover_capital'], 3),
                            'recover_wait_money' => bcadd($sum[0]['recover_interest'], $sum[0]['recover_capital'], 3),
                            'recover_wait_interest' => $sum[0]['recover_interest'],
                            'recover_wait_capital' => $sum[0]['recover_capital'],
                        );
                        if($v['recover_period'] == $tender_log['recover_times']){
                            $t_data['status'] = 'done';
                        }
                    }

                    $re = $this->tender_log->update($v['tender_id'], $t_data);
                    $write_repay_log .= " \r\n update_tender:".json_encode($re);

                    $param = array(
                        'uid'			=> $v['uid'],
                        'money'	    => $money,
                        'tob'			=> $tob,
                        'rel_data_id'	=> $v['id'],
                        'trans_id'		=> $v['id'].'_'.date('Ymd'),
                        'pot'			=> date('Ymd'),
                    );
                    $write_repay_log .= " \r\n api_param    :".json_encode($param);
                    $api = $this->api_fund->send($param);
                    $write_repay_log .= " \r\n api_result   :".json_encode($api);
                    if(isset($r_data)){
                        $re = $this->update($v['id'], $r_data);
                        $write_repay_log .= " \r\n update_repay  :".json_encode($re);
                    }


                    //今日收益(天天返)
                    if($borrow['repay_type'] == 'day'){
                    	if ($money_repayment) {
                    		$account = round($api['data']['balance'] - $money_repayment, 3);
                    	} else {
                    		$account = $api['data']['balance'];
                    	}
                        $sddata = array(
                            'uid'=>$v['uid'],
                            'rel_type'=>'every_day_interests',
                            'rel_data'=>array(
                                'money'=>$user_account_money,
                                'title'=>'今日收益',
                                'account'=>$account,
                                'logs'		=> array(
                                    array(
                                        'title'=>$borrow['title'],
                                        'borrow_id'=>$borrow['sn'],
                                        'tender_id'=>$v['tender_id'],
                                        'money'=>$user_account_money,
                                        'status'=>'成功',
                                        'wait_money'=>$t_data['recover_wait_interest'],
                                    ),
                                ),
                            ),
                            'ticket_id'=>strtotime(date('Y-m-d')),
                            'created'=>date('Y-m-d H:i:s'),
                        );
                        $this->user_account_log->income_today($sddata);
                    }
                    $write_repay_log .= " \r\n money_repay:".json_encode($money_repayment);
                    //还本
                    if($money_repayment){
                        $account_log = array(
                            'rel_data'      => array(
                                'money'=>$money_repayment,
                                'title'=>$borrow['title'],
                                'account'=>$api['data']['balance'],
                            ),
                            'rel_type'      => 'repayment',
                            'uid'           => $v['uid'],
                            'ticket_id'     => $v['id'],
                            'created'=>date('Y-m-d H:i:s'),
                        );
                        $write_repay_log .= " \r\n account_log:".json_encode($account_log);
                        $this->user_account_log->write($account_log);
                        $sms_data .= '|'.$v['uid'].'-'.'NO.'.$borrow['sn'].'-'.date('Y年m月d日H时i分s秒').'-'.$money_repayment.'-'.($borrow['period'] % 30 == 0 ? ($borrow['period'] / 30).'月' : $borrow['period'].'天')."/".($borrow['deal_flag'] == 'mortgage' ? '抵押' : '质押')."/".$borrow['apr'].'-'.$v['recover_interest'];
                    }
                }

                write_repay_log($write_repay_log);
            }
        }
        //如果是最后一个还本时间，修改标的状态为已完成
        if(date('Ymd', $borrow['repay_last_time']) == date('Ymd')){
            $this->borrow->update($borrow_id, array('status'=>'done'));
        }

        if($sms_data){
            $filename = FCPATH. '/data/repay_sms/'.date('Y-m-d').'.txt';
            @file_put_contents($filename, $sms_data, FILE_APPEND);
        }

        return TRUE;
    }
    /**
     * 发送投资还本短信
     * @return
     */
    public function sms_repay(){
        $filename = FCPATH. '/data/repay_sms/'.date('Y-m-d').'.txt';
        $content = @file_get_contents($filename);
        if($content){
            $sms_data = explode('|', $content);
            if(is_array($sms_data)){
                $this->load->model('user_model', 'user');
                foreach($sms_data as $v){
                    $arr = explode('-', $v);
                    if(is_array($arr) && $arr){
                        $user = $this->user->get($arr[0]);
                        $data = array(
                            'borrow_no'=>$arr[1],
                            'time'=>$arr[2],
                            'money'=>$arr[3],
                            'borrow_info'=>$arr[4],
                            'income'=>$arr[5],
                            'template'=>'repay_capital_template',
                        );
                        send_sms($user['mobile'], $data);
                    }
                }
            }
        }else{
            return;
        }
    }
    /**
     * replace插入数据
     *
     * @param  array  $data	//数据
     * @return array
     */
    public function replace($data){
        $table = $this->table;
        $re = $this->db->query("replace into t_".$table." (uid, borrow_id, borrower, tender_id, recover_period, recover_time, recover_amount, recover_interest, recover_capital, everyday_interest) values('".$data['uid']."', '".$data['borrow_id']."', '".$data['borrower']."', '".$data['tender_id']."','".$data['recover_period']."','".$data['recover_time']."','".$data['recover_amount']."','".$data['recover_interest']."','".$data['recover_capital']."','".$data['everyday_interest']."')");
        return $re;
    }

    /**
     * 根据借款ID获取还款记录
     * @param  integer  $bid  借款信息ID
     * @return array
     */
    public function get_by_borrow($bid){
        $data = $this->all(array('borrow_id'=>$bid));
        if($data){
            $this->load->model('user_identity_model', 'user_identity');
            foreach($data as $k=>$v){
                $user_identity = $this->user_identity->getIdentityByUid($v['uid']);
                $data[$k]['realname'] = $user_identity['realname'];
            }
        }

        return $data;
    }
    /**
     * 修改数据
     */
    public function modify($data, $where)
    {
        $this->db->where($where);
        return $this->db->update($this->table, $data) ? true : false;
    }
}