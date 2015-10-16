<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @大转盘抽奖model
 * @author wchuan
 * @license		图腾贷 [手机端][web端]
 * @category 2015-09-06
 * @version
 */
class User_lottery_model extends Base_model
{
	//抽奖的开始时间
    public $begin_time="2015-09-30 12:00:00"; //开始时间  0-不限制
    //抽奖的结束时间
    public $stop_time="2015-10-07 23:59:59";  //结束时间  0-不限制   
    //本次抽奖的奖项信息，必须按照从大到小的顺序进行填写，id为奖次，prize为中奖信息,v为中奖概率,num为奖品数量
	public $prize_num = array(
		'2015-09-30'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>1),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>1),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>1),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>1),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>1),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>1),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>10),
		),
		'2015-10-01'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>50),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>100),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>150),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>15),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>20),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>30),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>300),
		),
		'2015-10-02'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>50),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>100),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>150),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>15),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>20),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>30),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>300),
		),
		'2015-10-03'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>50),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>100),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>150),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>15),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>20),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>30),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>300),
		),
		'2015-10-04'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>50),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>100),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>150),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>15),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>20),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>30),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>300),
		),
		'2015-10-05'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>50),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>100),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>150),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>15),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>20),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>30),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>300),
		),
		'2015-10-06'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>50),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>100),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>150),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>15),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>20),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>30),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>300),
		),
		'2015-10-07'=>array(
			'0' => array('id' => 1, 'prize' => '3%加息券', 'v' => 3,'num'=>50),
			'1' => array('id' => 2, 'prize' => '2%加息券', 'v' => 5,'num'=>100),
			'2' => array('id' => 3, 'prize' => '1%加息券', 'v' => 8,'num'=>150),
			'3' => array('id' => 4, 'prize' => '2000特权金', 'v' => 10,'num'=>15),
			'4' => array('id' => 5, 'prize' => '1000特权金', 'v' => 11,'num'=>20),
			'5' => array('id' => 6, 'prize' => '500特权金', 'v' => 13,'num'=>30),
			'6' => array('id' => 7, 'prize' => '30-100个随机图腾币', 'v' => 50,'num'=>300),
		),
	);
	public function __construct()
    {
        parent::__construct();
		$this->table = 'user_lottery';
		$this->load->model('Recharge_model','recharge');
        $this->load->model('User_info_model','user_info');
		$this->load->model('plus_coupons_model','plus_coupons');
		$this->load->model('Points_model','points');
		date_default_timezone_set('PRC');
    }
	 public function _where($where)
    {
        if (!empty($where['uid'])) {
            $this->db->where("{$this->table}.uid=", $where['uid']);
        }
		if (!empty($where['win_id'])) {
            $this->db->where("{$this->table}.win_id=", $where['win_id']);
        }
		if (!empty($where['is_win'])) {
			$is_win= ($where['is_win']=='yes')?1:0;	
            $this->db->where("{$this->table}.is_win=", $is_win);
        }
        if (!empty($where['start_time'])) {
            $this->db->where("{$this->table}.created >=", $where['start_time']);
        }
        if (!empty($where['end_time'])) {
            $this->db->where("{$this->table}.created <=", $where['end_time']);
        }
        $this->db->order_by("{$this->table}.id desc, {$this->table}.created desc");
    }
	 /**
     * 生成中奖信息  uid:用户id，mobile:用户手机号码；
     */
    public function make($user=array()) {
        $uid=  (!empty($user["uid"]))?(int)  $user["uid"]:0;
		$username = (!empty($user["mobile"]))?hideMobile($user["mobile"]):'';
        if(empty($user) || empty($uid) || empty($username)){
			 return array("is_win"=>0,"title"=>"抽奖用户ID或用户手机号码不能为空");
        }
        if(!empty($this->begin_time) && time()<strtotime($this->begin_time)){
			 return array("is_win"=>0,"title"=>"抽奖还没有开始，开始时间为：".$this->begin_time);
        }
          
        if(!empty($this->stop_time) && time()>strtotime($this->stop_time)){
			 return array("is_win"=>0,"title"=>"本次抽奖已经结束，结束时间为：".$this->stop_time);
        }
         //获取奖项信息数组，来源于私有成员   
		$date_day = date("Y-m-d");
		//测试抽奖
		//$date_day = date("Y-m-d",strtotime("+2 day"));
		$prize_arr_num =  $this->prize_num;
		if(empty($prize_arr_num[$date_day])){
			return array("is_win"=>0,"title"=>"抽奖活动还未开始");
		}
		$prize_arr =  (!empty($prize_arr_num[$date_day]))?$prize_arr_num[$date_day]:array();
        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['v'];
        }
        //$rid中奖的序列号码
        $rid = $this->get_rand($arr); //根据概率获取奖项id
        $str = $prize_arr[$rid - 1]['prize']; //中奖项 
        //判断用户当天是否已经抽过奖
		$star_day = date("Y-m-d"." 00:00:00");
		$end_day = date("Y-m-d"." 23:59:59");
		$res_user = $this->count_all(array("uid"=>$uid,"start_time"=>$star_day,"end_time"=>$end_day));
		//国庆中秋活动新增2次抽奖机会
		if($res_user>=2){
			 return array("is_win"=>0,"title"=>"您今天的抽奖次数已经用完");
		}  
		//判断今天奖品已经抽完;
		$res_win = $this->count_all(array("win_id"=>$rid,"start_time"=>$star_day,"end_time"=>$end_day));
		if($res_win>=$prize_arr[$rid-1]['num']){
			$str='国庆快乐';
            $rid=0;
		}
        //生成一个用户抽奖的数据，用来记录到数据库
        $data=array(
            'uid'=>$uid,
            'nickname'=>$username,
			'win_id'=>$rid,
			'title'=>$str,
            'is_win'=>($rid==0)?0:1,
        );
        //将用户抽奖信息数组写入数据库
       $sv_result = $this->save($data);
	   if(!empty($sv_result)){
		   $cjtq_money = 0;
		   $plus_id=0;
		   $gold_point=0;
			switch($data['win_id']){
					case 1:
						$plus_id = 1;
					break;
					case 2:
						$plus_id = 2;
					break;	
					case 3:
						$plus_id = 3;
					break;	
					case 4:
						$cjtq_money = 2000;
					break;
					case 5:
						$cjtq_money = 1000;
					break;	
					case 6:
						$cjtq_money = 500;
					break;	
					case 7:
						$gold_point = rand(30,100);
					break;
			}   
			//自动发送特权金
			if($cjtq_money>0 && !empty($uid)){
				$userinfo = $this->user_info->get($uid);
				$this->recharge->add_counterfeit_money($userinfo,$cjtq_money,$sv_result,"lottery");
				$_SESSION['user_info'] = $this ->user_info->get($userinfo['uid']);
			}
			//自动发放加息券
			if($plus_id>0 && !empty($uid)){
				$this->plus_coupons->get_props($uid,$plus_id);
			}
			//自动发放图腾币
			if($gold_point>0 && !empty($uid)){
				$this->points->addPointsLog(array('uid'=>$uid,'tob'=>'activity','scores'=>$gold_point,'title'=>'国庆活动'));
				$str = $gold_point.'个图腾币';
				$this->save(array('title'=>$str),$sv_result);
			}
			 return array("is_win"=>$data['is_win'],"win_id"=>$data['win_id'],"status"=>'yes',"title"=>$str);
	   }else{
			 return array("is_win"=>0,"title"=>"抽奖失败"); 
	   }   
    }

	
	private function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度 
        $proSum = array_sum($proArr);
        //概率数组循环 
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }
}