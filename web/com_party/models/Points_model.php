<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @用户积分(图腾币)
 * @author zhoushuai
 * @license		图腾贷 [手机端，PC]
 * @category 2015-09-28
 * @version
 */
class Points_model extends Base_model
{
    public $tob = array(
        'check_in'=>'微信签到',
        'coupon'=>'加息券',
        'video'=>'实时监控',
        'sms'=>'短信包',
        //'other'=>'其他',
        'activity'=>'活动',
        'article'=>'转发文章',
        'award'=>'勋章奖励',
        'sys_give'=>'系统赠送',
        'invest'=>'投资',
    );
    public $tob_short = array(
        'check_in'=>'CI',
        'coupon'=>'CP',
        'video'=>'VD',
        'sms'=>'SM',
        //'other'=>'OT',
        'activity'=>'AC',
        'article'=>'AT',
        'award'=>'AW',
        'sys_give'=>'SG',
        'invest'=>'IV',
    );
    public $tob_get = array(
        'invest','check_in','activity','article','award','sys_give',
    );
    public $tob_use = array(
        'sms','video','coupon',
    );

    public function __construct()
    {
        parent::__construct('user_points_log');
        $this->load->model('User_model', 'user');
        $this->load->model('User_info_model', 'user_info');
    }



    public function _where($where)
    {
        if (isset($where['uid']) && !empty($where['uid'])) {
            $this->db->where("uid", $where['uid']);
        }

        if (isset($where['tob']) && !empty($where['tob'])) {
            $this->db->where("tob", $where['tob']);
        }

        if (isset($where['title']) && !empty($where['title'])) {
            $this->db->where("title", $where['title']);
        }


        if (isset($where['created']) && !empty($where['created'])) {
            $this->db->where("date_format(created,'%Y-%m-%d')", $where['created']);
        }
        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        } else {
            $this->db->order_by("{$this->pk} desc");
        }
    }



    /**
     * @积分(图腾币)记录
     * @param uid int 用户uid
     * @param tob string 业务类型
     * @param scores int 积分(图腾币)点数
     * @param round int 第几轮签到记录
     * @param title string 名称
     * @return boolean(true/false)
     */
    public function addPointsLog($data){
        $user = $this->user->get($data['uid']);
        $this->load->model('Dcredit_model', 'dcredit');
        $froum_credit = $this->dcredit->get_user_credit($user['mobile']);

        $round = empty($data['round'])?1:$data['round'];
        $userinfo = $this->user_info->get($data['uid']);
        $all_point = $userinfo['points'] + $froum_credit;
        if(in_array($data['tob'],$this->tob_get,true)){
            $points = $all_point + $data['scores'];
        }elseif(in_array($data['tob'],$this->tob_use,true)){
            if($userinfo['points']<$data['scores']){
                return false;
            }
            $points = $all_point - $data['scores'];
        }
        $pointlogs = array(
            'uid'    => $data['uid'], //用户ID
            'tob'    => $data['tob'],//业务类型
            'scores' => $data['scores'],//积分(图腾币)
            'round'  => $round,//第几轮签到记录
            'title'  => $data['title'],//名称
            'after_points' =>$points,//当前用户余额
            'created'=> date('Y-m-d H:i:s', time()), //创建时间
        );
        $this->db->trans_begin();
        parent::create($pointlogs);
        $dta['points'] = $userinfo['points'] + $data['scores'];
        $this->user_info->save($dta,$userinfo['uid']);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }
    }

    //使用积分【优先扣除网站积分，网站获得积分不足扣除论坛积分并记录】
    public function using($data){
        $uid = $data['uid'];
        $scores = $data['scores'];
        $this->load->model('Dcredit_model', 'dcredit');
        $userinfo = $this->user_info->get($uid);
        $user = $this->user->get($uid);

        $froum_credit = $this->dcredit->get_user_credit($user['mobile']);

        $all_point = $userinfo['points'] + $froum_credit;

        if($all_point < $scores){
            return false;
        }

        if($userinfo['points'] >= $scores){
            $pointsdata['points'] = $userinfo['points'] - $scores;
            $rest = 0;
        }else{
            $pointsdata['points'] = 0;
            $rest = $scores - $userinfo['points'];
            $pointsdata['used_froum_point'] = $rest + $userinfo['used_froum_point'];
        }
        $this->user_info->update($uid, $pointsdata);

        if($rest > 0){
            $this->dcredit->update_user_credit($user['mobile'], $rest);
        }

        $pointlogs = array(
            'uid'    => $data['uid'],
            'tob'    => $data['tob'],
            'scores' => $data['scores'],
            'title'  => $data['title'],
            'after_points' =>$all_point - $scores,
            'created'=> date('Y-m-d H:i:s', time()),
        );
        $this->create($pointlogs);
        return true;
    }












}
