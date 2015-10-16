<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 资金记录模型
 * zhang xiaojian
 */
class Account_log_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('api_fund_model', 'api_fund');
        $this->load->model('user_model','user');
    }
    /**
     * 获取用户相关账务流水
     * @param  string $keyword 用户手机号
     * @param  array $all_where 条件 startTime endTime limit offset
     * @return [array]      [返回array数据，可为空]
     */
    public function getList($keyword='',$search_where=array())
    {
        if ($keyword=='') {
            $list=$this->api_fund->getSearch($search_where,'AccLog');
            if ($list['error']!=1) {
                return null;
            }
            $list=$list['data']['result'];
            if (is_array($list)) {
                foreach ($list as $key => $value) {
                    $user=$this->user->get_mobile_by_uidarr(array($value['uid']));
                    if (!empty($user)) {
                        $list[$key]['mobile']=reset($user);
                    }
                }
            }
            return $list;
        }else{
            $user=$this->user->get_uid_by_mobile($keyword);
            if (is_null($user)) {
                return null;
            }else{
                $search_where['uid']=$user['uid'];
                $list=$this->api_fund->getSearch($search_where,'AccLog');
                $list=$list['data']['result'];
                if (is_array($list)) {
                    foreach ($list as $key => $value) {
                        $list[$key]['mobile']=$user['mobile'];
                    }
                }
                return $list;
            }
        }
    }
    /**
     * 获取数据数量
     * @param  string $keyword 用户手机号码
     * @param  array $search_where 查询条件
     * @return int          返回数据数量
     */
    public function getCount($keyword='',$search_where=array())
    {
        if ($keyword=='') {
            $list=$this->api_fund->getSearch($search_where,'AccLog');
            if ($list['error']==1) {
                return $list['data']['total'];   
            }
        }else{
            $user=$this->user->get_uid_by_mobile($keyword);
            if (is_null($user)) {
                return 0;
            }else{
                $search_where['uid']=$user['uid'];
                $list=$this->api_fund->getSearch($search_where,'AccLog');
                if ($list['error']==1) {
                    return $list['data']['total'];   
                }else{
                    return 0;
                }
            }
        }
    }
}
