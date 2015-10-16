<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @签到
 * @author zhoushuai
 * @license		图腾贷 [手机端，PC]
 * @category 2015-08-27
 * @version
 */
class Sign_model extends Base_model
{
    private $content;
    public function __construct()
    {
        parent::__construct('user_points_log');
        $this->load->model('Bind_model','bind');
        $this->load->model('Points_model','points');
        $this->load->model('User_info_model', 'user_info');
        $this->load->model('User_model', 'user');
    }



    /**
     * @获取最近签到
     */
    public function get_sign_by_uid($uid){
        $where = array(
            "{$this->table}.uid" => $uid,
            "tob"=>'check_in',
        );
        $this->db->select("*");
        $this->db->where($where);
        $this->db->order_by("{$this->table}.id desc, {$this->table}.created desc");
        $this->db->limit(1);
        $query = $this->db->get($this->table);
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }else{
            return array();
        }
    }

    public function _where($where)
    {
        if (isset($where['uid']) && !empty($where['uid'])) {
            $this->db->where("uid", $where['uid']);
        }

        if (isset($where['tob']) && !empty($where['tob'])) {
            $this->db->where("tob", $where['tob']);
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
     * @统计
     */
    public function count_all_sign($where){
        $result = parent::count_all($where);
        return $result;
    }



    /**
     * @提示语
     */
    private function creatContent(){
        $h=date('G');
        if ($h<11){
            $this->content = '早上好';
        }elseif($h<13){
            $this->content = '中午好';
        }elseif($h<17){
            $this->content = '下午好';
        }else{
            $this->content = '晚上好';
        }
        $kefu = array('阳阳','金金','莎莎','淘淘','肖肖','依依');
        $rand_keys = array_rand($kefu);
        $content =array("{$this->content}，我是客服{$kefu[$rand_keys]}","客服{$kefu[$rand_keys]}给您请安啦");
        $randkey = array_rand($content);
        $this->content = $content[$randkey]."\n";
    }

    /**
     * @领取加息券
     * @用户UID
     */
    private function getProps($uid){
        $this->load->model('Plus_coupons_model', 'plus_coupons');
        $res2 =$this->plus_coupons->get_props($uid);
        if($res2){
            if(is_numeric($res2) && $res2==3){
                $this->content.='您已领取3张加息券。<a href="'.site_url("autobid").'">设置加息券</a>';
            }else{
                $this->content.='同时获得3%加息券一张。<a href="'.site_url("autobid").'">设置加息券</a>';
            }
        }
    }


    /**
     * @签到抽奖
     * @获取奖品信息
     */
    private function doLottery($data){
        $this->load->model('user_lottery_model', 'lott');
        //uid:用户id，mobile:用户手机号码；
        $lott = $this->lott->make(array('uid'=>$data['uid'],'mobile'=>$data['mobile']));
        if($lott['status']=='yes'){
            if($lott['is_win']){
                if($data['round']==1){
                    $this->content .= "您已累计签到{$data['scores']}次，今日获得{$data['scores']}个图腾币！";
                    $this->content .= "\n/:handclap 恭喜您中奖：获得{$lott['title']}";
                    $this->content .= ",您还有一次【签到】机会。";
                }elseif($data['round']==2){
                    $scores = $data['scores']*$data['round'];
                    $this->content .= "您今日共获得{$scores}个图腾币！";
                    $this->content .= "\n/:handclap 恭喜您中奖：获得{$lott['title']}";
                }

            }else{
                if($data['round']==1){
                    $this->content .= "您已累计签到{$data['scores']}次，今日获得{$data['scores']}个图腾币！";
                    $this->content .= "\n/:handclap {$lott['title']}";
                    $this->content .= ",您还有一次【签到】机会。";
                }elseif($data['round']==2){
                    $scores = $data['scores']*$data['round'];
                    $this->content .= "您今日共获得{$scores}个图腾币！";
                    $this->content .= "\n/:handclap {$lott['title']}";
                }
            }
        }else{
            if($data['round']==1){
                $this->content .= "您已累计签到{$data['scores']}次，今日获得{$data['scores']}个图腾币！";
                $this->content .=  "\n/:P-({$lott['title']}";
            }elseif($data['round']==2){
                $scores = $data['scores']*$data['round'];
                $this->content .= "您今日共获得{$scores}个图腾币！";
                $this->content .=  "\n/:P-({$lott['title']}";
            }
        }
    }

    /**
     * @活动开始结束
     */
    private function activity(){
        if(ENVIRONMENT === 'production'){
            //开始时间
            $begin_time="2015-09-30 12:00:00"; //开始时间  0-不限制
            //结束时间
            $stop_time="2015-10-07 23:59:59";  //结束时间  0-不限制
            if(time()<strtotime($begin_time)){
                return false;
            }elseif(time()>strtotime($stop_time)){
                return false;
            }
        }
        return true;
    }


    /**
     * @第一次签到。记录。。增加图腾币(积分)
     * @param uid int 用户uid
     * @param tob string 业务类型
     * @param scores int 积分(图腾币)点数
     * @param round int 第几轮签到记录
     * @param title string 名称
     */
    private function _dosign($data){
        $data = array(
            'uid'=>$data['uid'],
            'tob'=>'check_in',
            'scores'=>$data['scores'],
            'mobile'=>$data['mobile'],
            'title'=>'微信签到',
            'round'=>1
        );
        $return = $this->points->addPointsLog($data);
        if($return){
            $this->creatContent();
            if($this->activity()){
                $this->doLottery($data);//抽奖
            }else{
                $this->content .= "您已累计签到{$data['scores']}次，今日获得{$data['scores']}个图腾币！";
            }
        }else{
            $this->content = '签到失败，请稍后再试！';
        }
    }


    /**
     * @再一次签到。随机
     */
    private function dosignAgain($rand,$data){
        if($data['round']>=$data['roundMx']){
            $this->content = '您已经签到过了，明天再来吧！';
        }else{
            $data['round']++;
            $data = array(
                'uid'=>$data['uid'],
                'tob'=>'check_in',
                'scores'=>$data['scores'],
                'mobile'=>$data['mobile'],
                'title'=>'微信签到',
                'round'=>$data['round']
            );
            $return = $this->points->addPointsLog($data);
            if($return){
                $this->creatContent();
                $evnt =get_rand($rand);
                if($evnt=='stop'){
                    $scores = $data['scores']*$data['round'];
                    $this->content .= "您今日共获得{$scores}个图腾币！";
                    $this->content .= "\n/:handclap 国庆快乐";
                }elseif($evnt=='again'){
                    $this->doLottery($data);//抽奖
                }
            }else{
                $this->content = '签到失败，请稍后再试！';
            }
        }
    }





    public function dosign($openid){
        if(empty($openid)){
            $this->content = '签到失败，请稍后再试！';
        }else{
            $where = array('openid'=>$openid);
            $bind = $this->bind->isBind($where);
            if(!$bind){
                $this->content="请先<a href='".site_url('login')."'>登陆</a>，再签到.";
            }else{
                $user = $this->user->get_user_info_by_uid($bind['uid']);
                if(empty($user)){
                    $this->content="您还没有注册账号，请先<a href='".site_url('register')."'>注册</a>，再签到.";
                }else{
                    $this->config->load('integral');
                    $integral = $this->config->item('integral');
                    $sign = $this->get_sign_by_uid($user['uid']);
                    $result = $integral['sign']($sign);
                    if($result['scores']){
                        $data = array(
                            'uid'=>$user['uid'],
                            'scores'=>$result['scores'],
                            'mobile'=>$user['mobile'],
                            'round'=>1
                        );
                        $this->_dosign($data);
                    }else{
                        if($this->activity()){
                            $rand_arr =array('stop'=>10,'again'=>90);
                            $data = array(
                                'uid'=>$user['uid'],
                                'scores'=>$sign['scores'],
                                'mobile'=>$user['mobile'],
                                'round'=>$sign['round'],
                                'roundMx'=>2,
                            );
                            $this->dosignAgain($rand_arr,$data);
                        }else{
                            $this->content = '您已经签到过了，明天再来吧！';
                        }
                    }
                }
            }
        }
        return $this->content;
    }
    /**
     * 打印日志
     * @param log 日志内容
     */
    protected function debuglog($data)
    {
        $log = '
#===============================================================================================================
#DEBUG-Sign(签到) start |执行时间：%s (ms)
#===============================================================================================================
#请求接口:%s
#User-Agent:%s
#返回数据(Array):
$data=%s;
#==================================================debug end====================================================' .
            "\r\n\r\n";
        debug_log($log, $data);
    }






}
