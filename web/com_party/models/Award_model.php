<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * zhang xiaojian
 * 奖励模型
 */
class Award_model extends Base_model {

    //活动奖励类型
    public $activity=array();

    public function __construct()
    {
        parent::__construct('user_bonus');
        $this->load->model('user_model','user');
        $this->load->model('manager_model','manager');
        $this->load->model('api_fund_model', 'api_fund');
        if ($this->load->config('bonus_activity')) {
            $this->activity=$this->config->item('Bonus_activity');
        }
        $this->pk='id';
    }
    /**
     * 获取奖励记录列表
     * @param  array   $where  查询条件
     * @param  integer $limit  每页数量
     * @param  integer $offset 查询偏移量
     * @return array           返货数组类型数据
     */
    public function getList($where=array(),$limit=10,$offset=0)
    {
    	$isHas=1;
    	$new_where=$this->getWhere($where,$isHas);
    	if ($isHas==0) {
    		return null;
    	}
    	$award_list=$this->search($new_where,$limit,$offset);
    	if (!is_array($award_list)) {
    		return null;
    	}else{
    			foreach ($award_list as $key => $value) {
                    //奖励类型
                    if (isset($this->activity[$value['activity']])) {
                        $award_list[$key]['activity']=$this->activity[$value['activity']];
                    }else{
                        $award_list[$key]['activity']='无此类型';
                    }
    				if ($where['mobile']=='') {
                        $userinfo = $this->user->get_mobile_by_uidarr(array($value['uid']));
		    			if (!empty($userinfo)) {
		    				$award_list[$key]['mobile']=reset($userinfo);
		    			}else{
		    				$award_list[$key]['mobile']=$value['uid'];
		    			}
	    			}else{
	    				$award_list[$key]['mobile']=$where['mobile'];
	    			}
	    			// 判断是否为手动操作
	    			if ($value['manager']!=0) {
                        $manager = $this->manager->get_user_by_id($value['manager']);
	    				if (!is_null($manager)) {
	    					$award_list[$key]['managername']=$manager['realname'];
	    				}
	    			}else{
	    				$award_list[$key]['managername']='系统';
	    			}
	    		}
    		return $award_list;
    	}
    }
    /**
     * 多条件处理获取奖励表相关条件
     * @param  array  $where 条件
     * @param  int  $isHas 是否符合条件
     * @return array        奖励表相关条件
     */
    private function getWhere($where=array(),&$isHas=1)
    {
    	$new_where=array();
    	if (isset($where['mobile'])) {
    		if ($where['mobile']!='') {
                $user = $this->user->get_uid_by_mobile($where['mobile']);
	    		if (!is_null($user)) {
	    			$new_where['uid']=$user['uid'];
	    		}else{
	    			$isHas=0;
	    		}
    		}
    	}
    	if (isset($where['type'])) {
    		if (intval($where['type'])!=0) {
    			if (intval($where['type'])==2) {
	    			$new_where['manager >']=0;
    			}elseif (intval($where['type'])==1) {
    				$new_where['manager']=0;
    			}
    		}
    	}
    	if (isset($where['rangetime'])) {
    		if ($where['rangetime']!='') {
	            $timearr=explode(' ',$where['rangetime']);
	            $new_where['created >'] =$timearr[0];
	            $new_where['created <'] =$timearr[2];
	        }
    	}
        if (isset($where['order_by'])) {
            $new_where['order_by']=$where['order_by'];
        }
    	return $new_where;
    }
    /**
     * 获取指定条件数据数量
     * @param  array  $where 条件数组
     * @return int        int数量
     */
    public function getCount($where=array())
    {
    	$isHas=1;
    	$new_where=$this->getWhere($where,$isHas);
    	if ($isHas==0) {
    		return 0;
    	}
        $this->count_all($new_where);
    	return $this->count_all($new_where);
    }
    /**
     * 添加奖励
     * @param  array  $data 添加数据
     * @return int       返回成功行id,0表示失败
     */
    public function createAward($data=array())
    {
        if (empty($data)) {
            return false;
        }else{
            $data['created']=date('Y-m-d H:i:s', time());
            $result = $this->db->insert('user_bonus',$data);
            $insert_id = $this->db->insert_id();
            admin_log('添加用户id为'.$data['uid'].'的奖励');
            if ($insert_id>0) {
                if($this->checkAward($insert_id)){
                   return true; 
                }else{
                    return false;
                }
            }
            return false;
        }
    }
    /**
     * 补单操作--修改用户资金，添加记录
     * @param  integer $id 奖励id
     * @return null
     */
    public function checkAward($id=0)
    {
        $award = $this->get($id);
        if (!is_null($award)) {
            $account = $this->api_fund->getRow(array('uid'=>$award['uid']),'Acc');
            //添加资金记录
            $time_arr=explode(' ',$award['created']);
            $timestr=$time_arr[0];
            $param = array(
                'uid'           => $award['uid'],
                'money'         => $award['money'],
                'tob'           => 'bonus',
                'rel_data_id'   => $id,
                'trans_id'      => $id,
                'pot'           => str_replace('-','',$timestr),
            );
            $result = $this->api_fund->send($param);
            if ($result['error']==1) {
                //写入资金流水
                Event::trigger('user_account_change',array(
                        'uid'=>$award['uid'],
                        'rel_type'=>'award',
                        'ticket_id'=>$id,
                        'rel_data'=>array(
                            'money'=>$award['money'],
                            'title'=>isset($this->activity[$award['activity']])?$this->activity[$award['activity']]:'奖励',
                            'account'=>$result['data']['balance'],
                            'logs'=>array(
                                array('status'=>'发放'.$this->activity[$award['activity']],'created'=>$award['created'],'success'=>1),
                                array('status'=>'发放成功','success'=>1))
                            )
                        )
                    );
                Event::trigger('user_news',array(
                            'uid'=>$award['uid'],
                            'trans_id'=>'useraward_'.$id,
                            'title'=>$this->activity[$award['activity']].'发放',
                            'data'=>json_encode(array('msg'=>$award['remark'],'status'=>'+'.$award['money'])),
                            'url'=>'/fund/index?rel_type=award')
                        );
                $this->update($id,array('status'=>1));
                return true;
            }
        }
        return false;
    }
}
