<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @加息券道具类
 * @author wangchuan
 * @category 2015-8-27
 * @version
 */
class Plus_coupons_model extends Props_model{
	//道具类型配置
    public function __construct()
    {
        parent::__construct();
    }
	//返回用户可用加息券
	public function use_coupons($uid=0,$props_str='1,2,3'){
		$uid = (int) $uid;
		if(empty($uid) || empty($props_str)){
			return NULL;
		}
		$use_time = strtotime(date("Y-m-d"));
        $props_arr = explode(',',$props_str);
		$this->db->where_in("{$this->table}.props_id", $props_arr);
        $this->db->where(array("uid"=>$uid,"used"=>0,"tob"=>"tender"));
        $this->db->where("{$this->table}.expires >=", $use_time);
		$this->db->order_by("{$this->table}.expires asc");
        $query = $this->db->get($this->table);
        $result = $query->result_array();
		$redata = array();
        $user_bag_config = $this->config->item('user_bag_type');
		if(!empty($result) && is_array($result)){
			foreach($result as $k=>$v){
				$redata[$k]=$v;
				$redata[$k]['name']=(isset($user_bag_config[$v['props_id']]['name']) && !empty($user_bag_config[$v['props_id']]['name']))?$user_bag_config[$v['props_id']]['name']:'';
				$redata[$k]['apr']=	(isset($user_bag_config[$v['props_id']]['apr']) && !empty($user_bag_config[$v['props_id']]['apr']))?$user_bag_config[$v['props_id']]['apr']:0;
			}
		}
		return $redata;
	}
	//判断加息券是否可用
	public function maybe_used($id=0,$uid=0,$props_str=''){
		$id=(int) $id;
		$uid=(int) $uid;
		if(empty($id) || empty($uid)){
			return false;
		}
		$result = $this-> get($id);
		if(empty($result)){
			return false;
		}
		if(!empty($props_str)){
			$props_arr = explode(",",$props_str);
			if(!in_array($result["props_id"],$props_arr)){
				return false;
			}
		}
		$use_time = strtotime(date("Y-m-d"));
		if($result["uid"]!=$uid  || $result["used"]>0 || $result["expires"]<$use_time || $result["tob"]!="tender"){
			return false;
		}
		return true;
	}
	//使用加息券parent::use_props($id)
	public function use_plus($id=0,$uid=0,$props_str){
		if(empty($props_str)){
			return false;
		}
		$mbused = $this->maybe_used($id,$uid,$props_str);
		if($mbused == false){
			return false;
		}
		return parent::use_props($id) ;
	}


	//获取单条加息券信息
	public function get_Ones($id=0){
		$id = (int) $id;
		if(empty($id)){
			return NULL;
		}
		$result = $this->get($id);
		if(empty($result)){
			return NULL;
		}
		$user_bag_config = $this->config->item('user_bag_type');
		$result["name"] = (isset($user_bag_config[$result['props_id']]['name']) && !empty($user_bag_config[$result['props_id']]['name']))?$user_bag_config[$result['props_id']]['name']:'';
		$result["apr"] = (isset($user_bag_config[$result['props_id']]['apr']) && !empty($user_bag_config[$result['props_id']]['apr']))?$user_bag_config[$result['props_id']]['apr']:0;
		return $result;
	}
	//加息后回调用
	public function sucess_coupons($id,$tender_id){
		$id = (int) $id;
		if(empty($id)||empty($tender_id)){
			return NULL;
		}
		$result = $this->db->where($this->pk, $id)
				->update($this->table, array("ticket_id"=>$tender_id));
		if($result === FALSE){
				return FALSE;
			}
		if ($this->db->affected_rows() > 0) {
			return $id;
		}
		else {
			return NULL;
		}
	}
	//领取加息券
	public function get_props($uid,$coupons=1){
		$uid = (int) $uid;
		if($uid>0){
			//获取活动时间
			$user_bags = $this->config->item('user_bag_type');
			if(empty($user_bags[$coupons]['activity_time'])){
				 log_message('error', '无法获取配置活动有效期','plus_coupons');
				 return false;
			}
			//验证时间是否符合活动时间
			$_d = explode('-', $user_bags[$coupons]['activity_time']);
			if($_d[0]>date('Ymd') || $_d[1]<date('Ymd')){
				return false;
			}
			//验证是否超过14次
			$result = $this->getWidgetTotal(array("uid"=>$uid,"in"=>array("props_id"=>array(1,2,3)),"scope"=>array("ltt"=>array('created'=>"2015-09-30 12:00:00"))));
			if($result>=14){
				return 14;
			}
			$expires  = (!empty($user_bags[$coupons]['expires']))?$user_bags[$coupons]['expires']:30;
			$res = $this->send_props(array("uid"=>$uid,"props_id"=>$coupons,"expires"=>strtotime("+ ".$expires ." day"),"tob"=>"tender"));
			if($res){
				return true;
			}else{
				return 	false;
			}
		}else{
			log_message('error', '无法获取用户UID','plus_coupons');
			return false;
		}
	}

}
