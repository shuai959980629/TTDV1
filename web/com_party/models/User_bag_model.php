<?php
/**
 * 道具包 MODEL
 * 
 * @package		MODEL
 * @author		LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://www.tutengdai.com)
 * @license		图腾贷
 * @link		http://www.tutengdai.com
 * @since		Version 1.0.0 2015-09-17
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User_bag_model extends Base_model 
{
	/**
	 * 主键ID
	 *
	 * @var string
	 */
	public     $pk    = 'id';

    public $tob_expires = array(
        'sms', 'video',
    );
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
    {
        parent::__construct('user_bag');

        $this->config->load("props");
        $this->load->model('Points_model', 'points');
        $this->load->model('User_bag_order_model', 'user_bag_order');
    }

    //获取关联业务配置
    public function get_bag_by_tob($tob){
        $user_bag_type = $this->config->item('user_bag_type');
        $bag_type =array();
        foreach($user_bag_type as $k=>$v){
            if($v['tob'] == $tob){
                $bag_type[$k]=$v;
            }
        }
        return $bag_type;
    }

    //购买道具
    public function buy($uid, $bag_key, $is_free=0){
        $user_bag_type = $this->config->item('user_bag_type');

        if(!array_key_exists($bag_key, $user_bag_type)){
            return false;
        }
        $bag = $user_bag_type[$bag_key];
        //扣除积分(积分兑换)
        if($is_free == 0){
            $point_data = array(
                'uid'    => $uid,
                'tob'    => $bag['tob'],
                'scores' => $bag['price'],
                'title'  => '购买'.$bag['name'],
            );
            $re = $this->points->using($point_data);
            if(!$re){
                return false;
            }
        }
        //生成订单
        $order_data = array(
            'uid'=>$uid,
            'props_id'=>$bag_key,
            'expires'=>time() + $bag['expires'] * 86400,
            'price'=>$is_free == 0 ? $bag['price'] : 0,
        );
        $re = $this->user_bag_order->create($order_data);
        if(!$re){
            return false;
        }
        //发放道具【1.一般道具直接生成记录，2.延长时间类道具根据当前用户最远到期时间加上】
        $this->provide($uid, $bag_key, $bag);
        return true;
    }

    //将道具放入用户背包
    public function provide($uid, $bag_key, $bag){
        if(isset($bag['package']) && is_array($bag['package']) && $bag['package']){
            $user_bag_type = $this->config->item('user_bag_type');
            foreach($bag['package'] as $v){
                if(in_array($user_bag_type[$v]['tob'], $this->tob_expires)){
                    $this->provide_expires($uid, $v, $user_bag_type[$v]);
                }else{
                    $this->provide_general($uid, $v, $user_bag_type[$v]);
                }

            }
        }else{
            if(in_array($bag['tob'], $this->tob_expires)){
                $this->provide_expires($uid, $bag_key, $bag);
            }else{
                $this->provide_general($uid, $bag_key, $bag);
            }
        }
        return true;
    }

    //购买延长时间类的产品（短信包、实时监控等）
    public function provide_expires($uid, $bag_key, $bag){
        //过期时间最远的短信包
        $last_bag = $this->getWidgetRow(array("uid"=>$uid, "tob"=>$bag['tob'], "order"=>"expires desc"));
        if($last_bag && $last_bag['expires'] > time()){
            $last_expires = $last_bag['expires'];
        }else{
            $last_expires = time();
        }
        $expires = $last_expires + $bag['expires'] * 86400;
        $data = array(
            'uid'=>$uid,
            'props_id'=>$bag_key,
            'expires'=>$expires,
            'tob'=>$bag['tob'],
        );

        if($this->create($data)){
            return true;
        }else{
            return false;
        }
    }

    //购买一般道具
    public function provide_general($uid, $bag_key, $bag){
        $data = array(
            'uid'=>$uid,
            'props_id'=>$bag_key,
            'expires'=>time() + $bag['expires'] * 86400,
            'tob'=>$bag['tob'],
        );

        if($this->create($data)){
            return true;
        }else{
            return false;
        }
    }

    //道具是否过期
    public function is_expires($uid, $tob){
        $user_bag = $this->user_bag->getWidgetRow(array("uid"=>$uid, "tob"=>$tob, "order"=>"expires desc"));

        if($user_bag && $user_bag['expires'] > time()){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    //检测是否用户达到VIP要求（总资产大于五万）并且还不是VIP自动添加VIP【定时任务】
    public function vip_job(){
        $this->load->model('user_model', 'user');
        $this->load->model('api_fund_model', 'api_fund');

        $users = $this->user->all(array('status'=>'allow'));

        if($users){
            foreach($users as $v){
                $ubr_where = array(
                    'uid'=>$v['uid'],
                    'props_id'=>8,
                    'expires>'=>time(),
                );

                $user_bag_order = $this->user_bag_order->getWidgetRow($ubr_where);

                if($user_bag_order){
                    continue;
                }

                $where  = array('uid' => $v['uid']);
                $opm    = 'Acc';
                $user_account = $this->api_fund->getRow($where, $opm);
                if($user_account['data']['amount'] >= 50000){
                    $re = $this->buy($v['uid'], 8, 1);
                }
            }
        }
        return true;
    }
}
?>