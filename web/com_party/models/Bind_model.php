<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @绑定第三方帐号
 * @author zhoushuai
 * @license		图腾贷
 * @copyright(c) 2015-08-13
 * @version
 */
class Bind_model extends Base_model{
    public $table;
    public $pk = 'id';
    public function __construct()
    {
        parent::__construct();
        $this->table = 'openid';
    }

    /**
     * @查询是否绑定
     * @param $where
     */
    public function isBind($where){
        $this->db->select('uid,openid')->from($this->table)->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }


    /**
     * @绑定
     * @param user 用户信息
     */
    public function userBind($user){
        $where = array(
            'uid'=>$user['uid'],
            'third_party'=>$user['third_party']
        );
        $bind = $this->isBind($where);
        if(!$bind){
            $this->db->insert($this->table, $user);
            if ($this->db->affected_rows() > 0) {
                $bid = $this->db->insert_id();
                return array('status'=>true,'msg'=>$bid);;
            } else {
                return array('status'=>false,'msg'=>'微信绑定失败！');
            }
        }else{
            if($bind['openid']!=$user['openid']){
                return array('status'=>false,'msg'=>'该帐号已经与其他微信号绑定！');
            }
            return array('status'=>true,'msg'=>null);
        }
    }

    /**
     * @解绑
     * @param uid 用户uid
     * @access public
     * @return boolean
     */
    public function delBind($uid)
    {
        $where = array(
            "{$this->table}.uid" => $uid,
            "{$this->table}.third_party"=>'weixin'
        );
        $this->db->delete($this->table, $where);
        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }else{
            return FALSE;
        }
    }


}
