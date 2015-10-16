<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @资金管理-充值管理
 * @author zhoushuai
 * @copyright(c) 2015-05-12
 * @version
 */
class Recharge_model extends Base_model
{
    //支付订单状态「wait：等待付款、paid：已付款、fail：失败、invalid：无效的」
    public $state = array(
        'wait'       => '等待付款',
        'paid'   => '已付款',
        'fail'    => '失败',
        'invalid'=>'无效的'
    );
    //支付渠道
    public $payment_code = array(
        'baofoo'=>'宝付',
        'yeepay'=>'易宝',
        'llpay' =>'连连支付',
        'llpay_wap' => '连连支付'
    );
    private $totalSmsPhone = array(
        '18628394457',
        '18628017405',
        '13981906901'
        );

    public function __construct()
    {
        parent::__construct('recharge_order');
        $this->load->model('User_model','usermodel');
        $this->load->model('User_info_model','User_info');
        $this->load->model('Borrow_model','borrow');
    }

    public function _where($where)
    {
        if (isset($where['username']) && !empty($where['username'])) {
            $this->load->model('User_model','user');
            $user = $this->user->get_uid_by_mobile($where['username']);
            $this->db->where('uid', intval($user['uid']));
        }

        if (isset($where['status']) && !empty($where['status'])) {
            $this->db->where('status', $where['status']);
        }

        if (isset($where['order_no']) && !empty($where['order_no'])) {
            $this->db->where('order_no =', $where['order_no']);
        }

        if (isset($where['start_time']) && !empty($where['start_time'])) {
            $this->db->where('created >=', $where['start_time']);
        }

        if (isset($where['end_time']) && !empty($where['end_time'])) {
            $this->db->where('created <=', "{$where['end_time']}:59");
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        } else {
            $this->db->order_by("{$this->pk} desc");
        }
    }


    /**
     * 获取单条数据
     * @param  array $where	//查询条件
     * @access public
     * @author ZHOUSHUAI
     * @return array
     */
    public function getRechargeOrder($where)
    {
        $this->db->where($where);
        $query = $this->db->get($this->table);
        $result= $query->row_array();
        $result['status'] = $this->state[$result['status']];
        $result['payment_code']=$this->payment_code[$result['payment_code']];
        return $result;
    }


    /**
     * @查看用户充值记录，包括：用户账号、订单号、充值渠道、充值金额、充值手续费 实际到账金额、当前状态、操作时间（最近一次）
     */
    public function query_list_recharge($where=array(),$limit = 50, $offset = 0){

        if (isset($where['username']) && !empty($where['username'])) {
            $this->load->model('User_model','user');
            $user = $this->user->get_uid_by_mobile($where['username']);
            $this->db->where('uid', intval($user['uid']));
        }

        if (isset($where['status']) && !empty($where['status'])) {
            $this->db->where('status', $where['status']);
        }

        if (isset($where['order_no']) && !empty($where['order_no'])) {
            $this->db->where('order_no =', $where['order_no']);
        }

        if (isset($where['start_time']) && !empty($where['start_time'])) {
            $this->db->where('created >=', $where['start_time']);
        }

        if (isset($where['end_time']) && !empty($where['end_time'])) {
            $this->db->where('created <=', "{$where['end_time']}:59");
        }

        $rechargelist = $this->search(array(),$limit,$offset);
        if (!empty($rechargelist)){
            return $rechargelist;
        }
        else {
            return array();
        }
    }

    /**
     * @统计
     */
    public function count_all_recharge($where){

        if (isset($where['username']) && !empty($where['username'])) {
            $this->load->model('User_model','user');
            $user = $this->user->get_uid_by_mobile(trim($where['username']));
            $this->db->where('uid', intval($user['uid']));
        }

        if (isset($where['status']) && !empty($where['status'])) {
            $this->db->where('status', $where['status']);
        }

        if (isset($where['order_no']) && !empty($where['order_no'])) {
            $this->db->where('order_no =', $where['order_no']);
        }

        if (isset($where['start_time']) && !empty($where['start_time'])) {
            $this->db->where('created >=', $where['start_time']);
        }

        if (isset($where['end_time']) && !empty($where['end_time'])) {
            $this->db->where('created <=', "{$where['end_time']}:59");
        }

        return $this->count_all();
    }
    /**
     * 统计充值金额
     * @param  [type] $fields [description]
     * @param  [type] $where  [description]
     * @return [type]         [description]
     */
    public function sum_all($fields,$where)
    {
        if (isset($where['username']) && !empty($where['username'])) {
            $this->load->model('User_model','user');
            $user = $this->user->get_uid_by_mobile(trim($where['username']));
            $this->db->where('uid', intval($user['uid']));
        }
        if (isset($where['status']) && !empty($where['status'])) {
            $this->db->where('status', $where['status']);
        }

        if (isset($where['order_no']) && !empty($where['order_no'])) {
            $this->db->where('order_no =', $where['order_no']);
        }

        if (isset($where['start_time']) && !empty($where['start_time'])) {
            $this->db->where('created >=', $where['start_time']);
        }

        if (isset($where['end_time']) && !empty($where['end_time'])) {
            $this->db->where('created <=', "{$where['end_time']}:59");
        }
        return $this->sum($fields,null);
    }
    /**
     * @根据id查询充值
     */
    public function query_recharge_by_id($where){
        $this->load->model('User_model','user');
        $this->load->model('Manager_model','manager');
        if (isset($where['id']) && !empty($where['id'])) {
            $this->db->where('id =', $where['id']);
        }

        $this->db->select($this->table . '.*')->limit(1);
        $query = $this->db->get($this->table);
        if ($query->num_rows() > 0){
            $data = $query->row_array();
            $userlist[]=$data['uid'];
            $usernamelist = $this->user->get_mobile_by_uidarr($userlist);
            $data['username'] = $usernamelist[$data['uid']];
            if(!empty($data['verifier'])){
                $adminor = $this->manager->get_user_by_id($data['verifier']);
                $data['adminor'] = $adminor['realname'];
            }
            return $data;
        }else{
            return array();
        }

    }
    /**
     * 提交资金记录
     * zhang xiaojian
     * @param integer $id 充值id
     */
    public function CheckLog($order_no,$check_amount)
    {
        $query = $this->db->get_where($this->table, array('order_no'=>$order_no));
        $recharg=$query->row_array();
        log_message('error',json_encode($recharg),'CheckLog_pay');
        if (!is_null($recharg) && $recharg['status'] == 'wait') {
            //检查充值金额
            // if ($recharg['amount'] != $check_amount) {
            //     return null;
            // }
            //修改用户资金
            $this->load->model('api_fund_model','api_fund');
            //添加资金记录
            $param = array(
                'uid'           => $recharg['uid'],
                'money'         => $recharg['money'],
                'tob'           => 'recharge_success',
                'rel_data_id'   => $recharg['id'],
                'trans_id'      => $recharg['id'],
                'pot'           => date('Ymd',strtotime($recharg['created'])),
            );
            $return_result = $this->api_fund->send($param);
            $up_result = 0;
            if ($return_result['error']==1) {
                //修改订单状态
                $up_result = $this->update($recharg['id'],array('status'=>'paid','paid_time'=>time()));   
            }
            if ($up_result > 0) {
                //写入资金流水
                Event::trigger('user_account_change',array(
                    'uid'=>$recharg['uid'],
                    'rel_type'=>'recharge',
                    'ticket_id'=>$up_result,
                    'rel_data'=>array(
                        'money'=>$recharg['money'],
                        'title'=>'在线充值',
                        'account'=>$return_result['data']['balance'],
                        'logs'=>array(
                            'data'=>array(
                                1=>array('status'=>'充值成功','created'=>$recharg['created'],'success'=>1)
                                )
                            )
                        )
                    )
                );
                $_news_log = array('msg'=>'在线充值('.$recharg['amount'].'元)','status'=>'充值成功');
                $news_data = array(
                    'uid'=>$recharg['uid'],
                    'trans_id'=>$recharg['id'],
                    'title'=>'在线充值',
                    'data'=>json_encode($_news_log),
                    'url'=>'/fund/index?rel_type=recharge',
                );
                Event::trigger('user_news', $news_data);

                // 特权金活动
                // 检查黑名单
                if ($this->check_blacklist($recharg['uid'])) {
                    $this->activity_money($recharg['uid'],$recharg['id']);   
                }

                return $return_result;
            }else{
                return $return_result;
            }
        }elseif ($recharg['status']=='paid') {
            return array('error'=>2);
        }elseif (is_null($recharg)) {
            return array('error'=>0);
        }else{
            return null;
        }
    }
    //特权金黑名单检查,是否通过
    public function check_blacklist($uid = 0)
    {
        $order_user=$this->usermodel->get($uid);
        $this->config->load('blacklist');
        $black_invite_query = $this->db->query("select uid from t_user_invite where invited_uid='".$uid."' and type='general'");
        $black_invite = $black_invite_query->row_array();
        $black_user_arr = $this->config->item('blacklist');
        //检查充值用户是否在黑名单帐户
        if (!in_array($order_user['mobile'],$black_user_arr)) {
            $invite_black_check = true;
            //检查当前充值用户的推荐用户是否在黑名单帐户
            if (!is_null($black_invite)) {
                $black_user=$this->usermodel->get($black_invite['uid']);
                if (in_array($black_user['mobile'],$black_user_arr)) {
                    $invite_black_check = false;
                }
            }
            if ($invite_black_check) {
                return true;
            }
        }
        return false;
    }
    //根据用户投标总量获得特权金最大额度
    public function get_max_counterfeit_money($uid = 0)
    {
        $max_counterfeit_money = 500000;
        //统计用户投标总量
        //获取特权标id start
        $all_try = $this->borrow->all(array('member_flag'=>'try'), array('id'));
        if($all_try){
            $borrow_id = array();
            foreach($all_try as $v){
                $borrow_id[] = $v['id'];
            }   
            $try_borrow_id = implode(',',$borrow_id);
        }else{
            $try_borrow_id = '';
        }
        $where_try_borrow = '';
        if ($try_borrow_id != '') {
            $where_try_borrow = ' and borrow_id not in('.$try_borrow_id.')';
        }
        //获取特权标id end
        $tender_query = $this->db->query("select sum(capital) as capital from t_tender_log where uid='".$uid."'".$where_try_borrow);
        $tender_all = $tender_query->row_array();
        if ($tender_all['capital']<50) {
            $max_counterfeit_money = 0;
        }elseif ($tender_all['capital']>=$max_counterfeit_money) {
            
        }else{
            $fmoney = $tender_all['capital']/10000;
            $max_counterfeit_money = ceil($fmoney) * 10000;
        }
        return $max_counterfeit_money;
    }
    //特权金活动处理
    public function activity_money($recharg_uid = 0,$recharg_id = 0)
    {
        //特权金活动
        $order_user=$this->usermodel->get($recharg_uid);
        $ty_start_time=strtotime('2015-08-03 00:00:00');
        $ty_end_time=strtotime('2016-12-30 23:59:59');
        $register_time=$order_user['created'];
        //是否为活动新用户
        if ($register_time>=$ty_start_time && $register_time<$ty_end_time) {
            
            //查询当前用户是否存在推荐人
            $invite_query = $this->db->query("select uid from t_user_invite where invited_uid='".$recharg_uid."' and type='general'");
            $invite = $invite_query->row_array();

            if (time()<$ty_end_time && !is_null($invite)) {

                //获取当前特权金
                $userinfo = $this->User_info->get($recharg_uid);
                $old_counterfeit_money=$userinfo['counterfeit_money'];

                //获取活动期内充值总额
                $db_query = $this->db->query("select sum(amount) as amount from t_recharge_order where uid='".$recharg_uid."' and status='paid' and paid_time>='".$ty_start_time."' and paid_time<'".$ty_end_time."'");
                $all_recharge = $db_query->row_array();

                //发放体验金给推荐人
                if ($all_recharge['amount']>=100) {

                    //推荐人每次特权金金额
                    $invite_money = 10000;
                    

                    //获取最高可获得特权金额度
                    $max_counterfeit_money = $this->get_max_counterfeit_money($invite['uid']);

                    // 通过特权金流水检查，推荐获取特权金总额是否已达上限
                    $counterfeit_query = $this->db->query("select sum(amount) as amount from t_counterfeit_money_log where uid='".$invite['uid']."' and tob='recommended'");
                    $counterfeit_all = $counterfeit_query->row_array();

                    //特权金是否达到最大值，未达到则进入发放流程
                    if ($counterfeit_all['amount']<$max_counterfeit_money) {

                        //检查是否已经对当前用户发放充值推荐体验金
                        $counterfeit_log = $this->db->query("select ticket_id from t_counterfeit_money_log where uid='".$invite['uid']."' and tob='recommended'");
                        $counterfeit = $counterfeit_log->result_array();
                        $check = true;
                        if (!empty($counterfeit) && !is_null($counterfeit)) {
                            foreach ($counterfeit as $key => $value) {
                                $rechargefield=$this->get($value['ticket_id']);
                                if ($rechargefield['uid']==$recharg_uid) {
                                    $check=false;
                                }
                            }
                        }
                        if ($check) {
                            //判断当前用户当次特权金金额
                            if (($invite_money + $counterfeit_all['amount'])>$max_counterfeit_money) {
                                $invite_money = $max_counterfeit_money - $counterfeit_all['amount'];
                            }elseif (($max_counterfeit_money - $counterfeit_all['amount'])<=0) {
                                $invite_money = 0;
                            }
                            if ($invite_money>0) {
                                $iuserinfo = $this->User_info->get($invite['uid']);
                                $this->add_counterfeit_money($iuserinfo,$invite_money,$recharg_id,'recommended');
                            }
                        }
                    }
                    
                }
                //充值人特权金流程
                $first_money = 2000;
                $second_money = 10000;
                //统计充值获得的特权金总额
                $amount_query = $this->db->query("select sum(amount) as amount from t_counterfeit_money_log where tob='recharge' and uid='".$recharg_uid."'");
                $ty_logs = $amount_query->row_array();

                //统计获得特权金总额
                $user_counterfeit_query = $this->db->query("select sum(amount) as amount from t_counterfeit_money_log where uid='".$recharg_uid."' and tob in ('recharge','recommended')");
                $user_counterfeit_all = $user_counterfeit_query->row_array();

                //加条件，限制新用户特权金总额只能为充值获得的
                if($ty_logs['amount']<($first_money+$second_money)){
                    //检查活动期内，总充值金额所在特权金范围，>=100 && <20000
                    if ($all_recharge['amount']>=100 && $all_recharge['amount']<20000) {

                        //是否存在充值获得特权金流水
                        if (is_null($ty_logs['amount'])) {
                            $this->add_counterfeit_money($userinfo,$first_money,$recharg_id,'recharge');
                        }elseif ($ty_logs['amount']<$first_money) {
                            $ty_money=2000-$ty_logs['amount'];
                            $this->add_counterfeit_money($userinfo,$ty_money,$recharg_id,'recharge');
                        }

                    }elseif ($all_recharge['amount']>=20000) {
                        
                        //体验金12000
                        if (is_null($ty_logs['amount'])) {
                            $this->add_counterfeit_money($userinfo,$first_money+$second_money,$recharg_id,'recharge');
                        }elseif ($ty_logs['amount']<($first_money+$second_money)) {
                            $ty_money=($first_money+$second_money)-$ty_logs['amount'];
                            $this->add_counterfeit_money($userinfo,$ty_money,$recharg_id,'recharge');
                        }
                    }
                    $_SESSION['user_info'] =$this ->User_info->get($recharg_uid);
                }
            }
        }
    }
    //发放特权金
    public function add_counterfeit_money($userinfo = array(),$money = 0,$ticket_id = 0,$type='recharge')
    {
        $this->db->trans_start();
        //1.写入体验金日志
        $this->db->query("insert into t_counterfeit_money_log(amount,tob,uid,ticket_id,created) values('".$money."','".$type."','".$userinfo['uid']."','".$ticket_id."',".time().")");
        $insert_id=$this->db->insert_id();
        //2.充值体验金金额
        $new_money = $userinfo['counterfeit_money'] + $money;

        $this->User_info->save(array('counterfeit_money'=>$new_money),$userinfo['uid']);
        $this->db->trans_complete();
        //写入资金流水
        Event::trigger('user_account_change',array(
            'uid'=>$userinfo['uid'],
            'rel_type'=>'award',
            'ticket_id'=>$insert_id,
            'rel_data'=>array(
                'money'=>$money,
                'title'=>'发放特权金',
                'account'=>$userinfo['counterfeit_money'],
                'logs'=>array(
                    array('status'=>'发放特权金','created'=>date('Y-m-d H:i:s'),'success'=>1),
                    array('status'=>'发放成功','success'=>1))
                )
            )
        );
    }

    /**
     * 统计前一天充值金额,用于定时任务
     * @return bool
     */
    public function sendRechargeTotal()
    {
        $start_time = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_time = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $this->db->where('paid_time >=', strtotime($start_time));
        $this->db->where('paid_time <=', strtotime($end_time));
        $this->db->where('status','paid');
        $this->db->group_by('payment_code');
        $this->db->select_sum('amount');
        $this->db->select('payment_code');
        $query = $this->db->get($this->table);
        $result = $query->result_array();
        $sendData = array(
            'template'=>'recharge_total_template',
            'start_time'=>$start_time,
            'end_time'=>$end_time,
            'baofoo'=>'0.00',
            'yeepay'=>'0.00',
            'llpay'=>'0.00');
        if ($result) {
            foreach ($result as $key => $value) {
                if ($value['payment_code'] == 'llpay_wap') {
                    $sendData['llpay'] += $value['amount'];
                }else{
                    $sendData[$value['payment_code']] += $value['amount'];
                }
            }
        }
        foreach ($this->totalSmsPhone as $key => $value) {
            $send_result = send_sms($value,$sendData);
            //日志记录
            log_message('error', json_encode(array('phone'=>$value,'send_result'=>$send_result,'send_data'=>$sendData)), 'recharge_send');
        }
    }
}
