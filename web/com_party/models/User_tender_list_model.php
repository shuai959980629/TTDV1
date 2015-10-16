<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 榜单排名统计
 * @author zhang xiaojian
 */
class User_tender_list_model extends Base_model{
	
    public function __construct()
    {
        parent::__construct('user_tender_list');
        $this->pk = 'id';
    }
    public function checkData()
    {
    	$this->load->model('User_model','user');
    	$this->load->model('User_info_model','user_info');
    	//统计总投资
        $int_time=time();
    	$allquery = $this->db->query("select (borrow_one_count+borrow_two_count+borrow_three_count+borrow_mortgage_count) as money,uid from t_user_account_count order by money desc limit 10");
        $all_count = $allquery->result_array();
        $this->db->trans_start();
        $this->db->query("delete from t_user_tender_list where type='year'");
        foreach ($all_count as $key => $value) {
            $user_mobile = $this->user->get_mobile_by_uidarr(array($value['uid']));
            $value['mobile']=current($user_mobile);
            $value['type']='year';
            $value['created']=$int_time;
            $user_info = $this->user_info->get($value['uid']);
            $value['nickname']=!empty($user_info['nickname'])?$user_info['nickname']:'';
            $this->create($value);
        }
        //统计月投资
        $start_time=date('Y-m');
        $end_time=date('Y-m',strtotime('+1 month'));
        $monthquery=$this->db->query("select sum(capital) as money,uid from t_tender_log where status in('going','early','done','transfer_success') and created>='".$start_time."' and created<'".$end_time."' group by uid order by money desc limit 10");
        $month_count = $monthquery->result_array();
        $this->db->query("delete from t_user_tender_list where type='month'");
        foreach ($month_count as $mkey => $mvalue) {
            if ($mvalue['uid']!=0) {
                $user_mobile = $this->user->get_mobile_by_uidarr(array($mvalue['uid']));
                $mvalue['mobile']=current($user_mobile);
                $mvalue['type']='month';
                $mvalue['created']=$int_time;
                $user_info = $this->user_info->get($mvalue['uid']);
                $mvalue['nickname']=!empty($user_info['nickname'])?$user_info['nickname']:'';
                $this->create($mvalue);   
            }
        }
        $this->db->trans_complete();
    	//统计月总投资
    }
}
