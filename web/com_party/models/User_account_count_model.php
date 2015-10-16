<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * zhang xiaojian
 * 用户资金统计模型
 */
class User_account_count_model extends Base_model {

    private $start_time=0;
    private $end_time=0;
    public function __construct()
    {
        parent::__construct('user_account_count');
        $this->load->model('User_income_daybyday_model','user_income_daybyday');
        $this->load->model('Api_fund_model','api_fund');
        $this->load->model('repay_log_model'		, 'repay_log');
        $this->pk='uid';
    }
    /**
     * 创建统计
     * @param  array  $data 传入参数
     * @return bool     
     */
    public function create($data=array())
    {
        if (!isset($data['uid'])) {
           return false; 
        }else{
            $result = parent::create($data);
            if (is_null($result)) {
                return false;
            }else{
                return true;
            }
        }
    }
    /**
     * 统计修改
     * @param  array   $data 修改字段信息
     * @param  integer $id   传入uid
     * @return bool        
     */
    public function save($data=array(),$id=0)
    {
        $result =parent::save($data,$id);
        if (is_null($result)) {
            return false;
        }else{
            return true;
        }
    }
    /**
     * 统计
     */
    public function saveCount()
    {
        //查询所有用户
        $user_query = $this->db->query("select uid from t_user where status='allow'");
        $users = $user_query->result_array();
        if (empty($users)) {
            return;
        }
        $this->end_time=time();
        $this->start_time=0;
        foreach ($users as $key => $value) {
            $save_data=$this->get($value['uid']);
            $old_data = $save_data;
            if (isset($save_data['updated'])) {
                $this->start_time=$save_data['updated'];
            }
            //充值以及手续费查询
            $this->recharge_count($value['uid'],$save_data);
            //投标统计
            $this->tender_count($value['uid'],$save_data);
            //提现总额,以及手续费查询
            $this->fee_count($value['uid'],$save_data);
            //今日收益
            $this->income_today_count($value['uid'],$save_data);
            //存今日收益到数据表
            $this->setIncomeDay($value['uid'],$save_data['income_today_count'],time());
            //统计近一月，近一周收益
            $this->income_count_count($value['uid'],$save_data);
            //总收益
            $this->income_count($value['uid'],$save_data);
            //奖励总额
            $this->bonus_count($value['uid'],$save_data);

            $save_data['other_count']=0;
            //所有流水＝充值＋投资＋提现＋冻结＋总收益＋奖励＋其他
            $save_data['all_count']=$save_data['borrow_one_count']+$save_data['borrow_two_count']+$save_data['borrow_three_count']+$save_data['borrow_mortgage_count']+$save_data['recharge_count']+$save_data['cash_count']+$save_data['income_count']+$save_data['bonus_count']+$save_data['other_count'];
            if (!isset($save_data['uid'])) {
                $save_data['uid']=$value['uid'];
                $save_data['created']=$this->end_time;
                $save_data['updated']=$this->end_time;
                $this->create($save_data);
            } else {
                $check_result=false;
                foreach ($old_data as $okey => $ovalue) {
                    $old_fl=floatval($ovalue);
                    $new_fl=floatval($save_data[$okey]);
                    if (bccomp($old_fl,$new_fl,3)) {
                        $check_result=true;
                        break;
                    }
                }
                if ($check_result) {
                    unset($save_data['uid']);
                    $save_data['updated']=$this->end_time;
                    log_message('error',json_encode(array('old_data'=>$old_data,'data'=>$save_data,'uid'=>$value['uid'])),'save_count_log');
                    $this->save($save_data,$value['uid']);
                }
            }
            unset($save_data);
            unset($old_data);
        }
    }
    //充值以及手续费查询--统计
    public function recharge_count($uid,&$save_data)
    {
        $recharge_query = $this->db->query("select sum(money) as money,sum(fee) as fee,paid_time from t_recharge_order where uid='".$uid."' and status='paid' and paid_time>=".$this->start_time." and paid_time<=".$this->end_time);
        $recharge_result = $recharge_query->result_array();
        if (!isset($save_data['recharge_count'])) {
            $save_data['recharge_count']=0;
        }
        if (is_null($recharge_result[0]['money'])) {
            $save_data['recharge_count']+=0;
        } else {
            $save_data['recharge_count']+=$recharge_result[0]['money'];
        }
        //手续费总额
        if (!isset($save_data['fee_count'])) {
            $save_data['fee_count']=0;
        }
        if (is_null($recharge_result[0]['fee'])) {
            $save_data['fee_count']+=0;
        } else {
            $save_data['fee_count']+=$recharge_result[0]['fee'];
        }
    }
    //投标统计--统计
    public function tender_count($uid,&$save_data)
    {
        $where=" and created>='".date('Y-m-d H:i:s',$this->start_time)."' and created<='".date('Y-m-d H:i:s',$this->end_time)."'";
        $sql="select borrow_id,capital from t_tender_log where uid='".$uid."' and status in ('pendding','going','early','done','transfer_success')".$where;
        $tender_query = $this->db->query($sql);
        $tender_result = $tender_query->result_array();
        $borrow_one_count=0;
        $borrow_two_count=0;
        $borrow_three_count=0;
        $borrow_mortgage_count=0;
        if (!empty($tender_result)) {
            foreach ($tender_result as $key => $value) {
                $borrow_query = $this->db->query("select period,deal_flag from t_borrow where id=".$value['borrow_id']);
                $borrow_result = $borrow_query->result_array();
                log_message('error',json_encode(array('data'=>$borrow_result,'uid'=>$uid)),'tender_count_log');
                if ($borrow_result[0]['deal_flag']=='mortgage') {
                    $borrow_mortgage_count+=$value['capital'];
                }else{
                    if ($borrow_result[0]['period']==30) {
                        $borrow_one_count+=$value['capital'];
                    }else if ($borrow_result[0]['period']==60) {
                        $borrow_two_count+=$value['capital'];
                    }else if ($borrow_result[0]['period']==90) {
                        $borrow_three_count+=$value['capital'];
                    }
                }
            }
        }
        if (!isset($save_data['borrow_one_count'])) {
            $save_data['borrow_one_count']=0;
            $save_data['borrow_two_count']=0;
            $save_data['borrow_three_count']=0;
            $save_data['borrow_mortgage_count']=0;
        }
        $save_data['borrow_one_count']+=$borrow_one_count;
        $save_data['borrow_two_count']+=$borrow_two_count;
        $save_data['borrow_three_count']+=$borrow_three_count;
        $save_data['borrow_mortgage_count']+=$borrow_mortgage_count;
    }
    //提现总额,以及手续费查询--统计
    public function fee_count($uid,&$save_data)
    {
        $where=" and created>='".date('Y-m-d H:i:s',$this->start_time)."' and created<='".date('Y-m-d H:i:s',$this->end_time)."'";
        $cash_query = $this->db->query("select sum(amount) as amount,sum(fee) as fee from t_cash_order where uid='".$uid."' and status in ('passed','done')".$where);
        $cash_result = $cash_query->result_array();
        if (!isset($save_data['cash_count'])) {
            $save_data['cash_count']=0;
        }
        if (is_null($cash_result[0]['amount'])) {
            $save_data['cash_count']+=0;
        } else {
            $save_data['cash_count']+=$cash_result[0]['amount'];
        }
        //手续费总额
        if (!isset($save_data['fee_count'])) {
            $save_data['fee_count']=0;
        }
        if (is_null($cash_result[0]['fee'])) {
            $save_data['fee_count']+=0;
        } else {
            $save_data['fee_count']+=$cash_result[0]['fee'];
        }
    }
    
    /**
     * 统计 今日收益数据
     *
     * @param  integer $uid			//用户UID
     * @param  array   $save_data	//数据容器
     * @author LEE [改]
     * @copyright 201507xx
     * @return void
     */
    public function income_today_count($uid, &$save_data)
    {
        $r = array(
    		'cols'		=> array('sum(everyday_interest) AS mon1'),
    		'uid'		=> $uid,
    		'in'		=> array('status' => array('wait', 'done')),
    		'scope'		=> array(
    			'lt'  => array('everyday_interest' => 0),
    			'ltt' => array('recover_time' => strtotime(date('Y-m-d'))),
    			'mtt' => array('created' => date('Y-m-d', time() - 86400) . ' 23:59:59')
    		),
    	);
    	$interest_1 = $this->repay_log->getWidgetRow($r);
    	$r = array(
    		'cols'		=> array('sum(recover_interest) AS mon2'),
    		'uid'		=> $uid,
    		'in'		=> array('status' => array('wait', 'done')),
    		'everyday_interest' => 0,
    		'scope' 	=> array(
    			'ltt' => array('recover_time' => strtotime(date('Y-m-d'))),
    			'mt'  => array('recover_time' => (strtotime(date('Y-m-d')) + 86399))
    		),
    	);
    	$interest_2 = $this->repay_log->getWidgetRow($r);
    	$save_data['income_today_count']  = !empty($interest_1['mon1']) ? $interest_1['mon1'] : 0;
    	$save_data['income_today_count'] += !empty($interest_2['mon2']) ? $interest_2['mon2'] : 0;
    }
    
    /**
     * 统计 最近一月、最近一周收益数据
     *
     * @param  integer $uid			//用户UID
     * @param  array   $save_data	//数据容器
     * @author LEE [改]
     * @copyright 20150828
     * @return void
     */
    public function income_count_count($uid,&$save_data)
    {
    	$save_data['income_week_count'] = $save_data['income_month_count'] = 0;
    	
    	$where = array(
    		'cols'	=> array('income'),
    		'eq' 	=> array('uid' => $uid),
    		'order'	=> array('dateTime' => 'desc')
    	);
        $result = $this->user_income_daybyday->getWidgetRows($where);
        
        if (!empty($result)) {
        	foreach ($result as $key=>$value) {
        		$key < 7 && $save_data['income_week_count'] += $value['income'];
        		$save_data['income_month_count'] += $value['income'];
        	}
        }
    }
    //总收益,待收--统计
    public function income_count($uid,&$save_data)
    {
        $income_count_query = $this->db->query("select sum(repay_total_interest) as repay_total_interest from t_tender_log where uid='".$uid."' and status in ('going','early','done','transfer_success')");
        $income_count_result = $income_count_query->result_array();
        //总收益
        if (is_null($income_count_result[0]['repay_total_interest'])) {
            $save_data['income_count']=0;
        } else {
            $save_data['income_count']=$income_count_result[0]['repay_total_interest'];
        }
        $income_count_query = $this->db->query("select sum(recover_wait_interest) as recover_wait_interest,sum(recover_wait_capital) as recover_wait_capital from t_tender_log where uid='".$uid."' and status='going'");
        $income_count_result = $income_count_query->result_array();
        //待收总利息
        if (is_null($income_count_result[0]['recover_wait_interest'])) {
            $save_data['await_count']=0;
        } else {
            $save_data['await_count']=$income_count_result[0]['recover_wait_interest'];
        }
        //待收本金
        if (is_null($income_count_result[0]['recover_wait_capital'])) {
            $save_data['await_corpus_count']=0;
        } else {
            $save_data['await_corpus_count']=$income_count_result[0]['recover_wait_capital'];
        }
        //待收个数
        $await_count_query = $this->db->query("select count(id) as all_count from t_tender_log where uid='".$uid."' and status='going'");
        $await_count_result = $await_count_query->result_array();
        if (is_null($await_count_result[0]['all_count'])) {
            $save_data['await_count_count']=0;
        } else {
            $save_data['await_count_count']=$await_count_result[0]['all_count'];
        }
    }
    //奖励总额--统计
    public function bonus_count($uid,&$save_data)
    {
        $where=" and created>='".date('Y-m-d H:i:s',$this->start_time)."' and created<='".date('Y-m-d H:i:s',$this->end_time)."' and status=1";
        $bonus_query = $this->db->query("select sum(money) as money from t_user_bonus where uid='".$uid."'".$where);
        $bonus_result = $bonus_query->result_array();
        if (!isset($save_data['bonus_count'])) {
            $save_data['bonus_count']=0;
        }
        if (is_null($bonus_result[0]['money'])) {
            $save_data['bonus_count']+=0;
        } else {
            $save_data['bonus_count']+=$bonus_result[0]['money'];
        }
    }
    /**
     * 修改统计的即时字段await_count,await_corpus_count,await_count_count
     * @param  array  $data 
     * @return bool      
     */
    public function upData($data=array(),$uid=0)
    {
        $olddata=$this->get($uid);
        if (is_null($data)) {
            return false;
        } else {
            if (isset($data['await_count'])) {
                $data['await_count']=floatval($data['await_count']);
                $newdata['await_count']=$data['await_count']+$olddata['await_count'];
            }
            if (isset($data['await_corpus_count'])) {
                $data['await_corpus_count']=floatval($data['await_corpus_count']);
                $newdata['await_corpus_count']=$data['await_corpus_count']+$olddata['await_corpus_count'];
            }
            if (isset($data['await_count_count'])) {
                $data['await_count_count']=intval($data['await_count_count']);
                $newdata['await_count_count']=$data['await_count_count']+$olddata['await_count_count'];
            }
            $olddata=$this->get($uid);
            if (is_null($olddata)) {
                $newdata['uid']=$uid;
                return $this->create($newdata);
            } else {
                return $this->save($newdata,$uid);   
            }
        }
        
    }


    /**
     * 设置一条当日收益
     *
     * @param  integer $uid		 //用户UID
     * @param  float   $income	 //当日收益
     * @param  integer $dateTime //当日时间戳
     * @author LEE
     * @access private
     * @return boolean
     */
    private function setIncomeDay($uid, $income, $dateTime)
    {
    	$dateTime = strtotime(date('Ymd', $dateTime));
    	
    	//查询是否存在当日收益
    	$where = array(
    		'uid' 		=> $uid,
    		'dateTime'	=> $dateTime
    	);
    	$rs = $this->user_income_daybyday->getRow($where);
    	if (!empty($rs)) {
    		return false;
    	}
    	
    	//不存在就添加一条当日的
    	$data = array(
    		'uid'		=> $uid,
    		'dateTime'	=> $dateTime,
    		'income'	=> $income,
    		'created'	=> time()
    	);
    	$this->user_income_daybyday->createRow($data);
    	
    	//删除超出30日数据
    	$this->delIncomeDay($uid);
    	
    	return true;
    }
    
    /**
     * 删除超出30日数据
     * 
     * @param  integer	//用户UID
     * @access private
     * @author LEE
     * @return boolean
     */
    private function delIncomeDay($uid)
    {
    	$where = array(
    		'uid' 		=> $uid,
    		'dateTime'	=> strtotime(date('Ymd')) - 86400 * 30	//时间戳
    	);
    	return $this->user_income_daybyday->del($where);
    }
}
