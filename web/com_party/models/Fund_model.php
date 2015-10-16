<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 用户资金表－－user_account
 * zhang xiaojian 
 */
class Fund_model extends Base_model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('api_fund_model', 'api_fund');
        $this->load->model('user_model','user');
    }
    /**
     * 获取平台用户资金信息
     * @param  string $keyword 传入关键词
     * @return array      返回数组类型用户资金信息
     */
    public function getAccount($keyword ='',$limit=10,$offset=0)
    {
        if (!empty($offset)) {
            $this->db->limit($limit, $offset);
        }
        else {
            $this->db->limit($limit);
        }
        if ($keyword=='') {
            $userinfo =$this->db->get('user');
        }else{
            $userinfo=$this->db->or_where('uid',intval($keyword))->or_where('mobile',$keyword)->get('user');
        }
        $userarr =$userinfo->result_array();
        if (is_null($userarr) || !is_array($userarr)) {
            return null;
        }
        foreach ($userarr as $key => $value) {
            $account = $this->api_fund->getRow(array('uid'=>$value['uid']),'Acc');
            if (!empty($account) && !is_null($account)) {
                $userarr[$key]['account'] =$account['data'];
            }
        }
        return $userarr;
    }
    /**
     * 获取指定id的用户的account
     * @param  integer $uid 用户id
     * @return array       返回数组类型数据
     */
    public function getAccountById($uid=0)
    {
        if ($uid<=0) {
            return null;
        }else{
            $account = $this->api_fund->getSearch(array('uid'=>$uid),'Acc');
            if ($account['error']==1) {
                return $account['data']['result'];   
            }else{
                return null;
            }
        }
    }
    /**
     * 获取指定条件下记录数量
     * @param  string  $keyword 关键词
     * @return int        返回纪录数量
     */
    public function getCount($keyword='')
    {
        $where = array();
        if ($keyword!='') {
            $where['mobile']=$keyword;
        }
        return $this->user->count_all($where);
    }
}
