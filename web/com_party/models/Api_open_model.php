<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @第三方授权模块
 * @author wangchuan
 * @category 2015-5-12
 * @version
 */
class Api_open_model extends Base_model{
    public $table;
    public function __construct()
    {
        parent::__construct();
        $this->table = 'openid';
    }
	//通过access_token单条数据
	public function get_row_by_accesstoken($access_token){
		$where = array(
            "{$this->table}.access_token" => $access_token,
			"{$this->table}.third_party" => 'tuteng',
        );
        $query = $this->db->get_where($this->table, $where);
        return $query->row_array();
	}
	//通过uid获取单条数据
	public function get_row_by_uid($uid){
		$where = array(
            "{$this->table}.uid" => $uid,
			"{$this->table}.third_party" => 'tuteng',
        );
        $query = $this->db->get_where($this->table, $where);
        return $query->row_array();
	}
	//通过UID数组查询微信是否绑定
	//通过uid 数组获取用户名
	public function get_wx_by_uidarr($data=array()){
		$this->db->select('id,uid,third_party,openid');
		$this->db->where_in("{$this->table}.uid", $data);
		$this->db->where(array("third_party"=>"weixin"));
		$query = $this->db->get($this->table);
        if ($query->num_rows() > 0) {
			//返回一维数组
			$returns = $query->result_array();
			$redata = '';
			foreach($returns as $k => $v){
				$redata[$v['uid']]=$v['id'];
			}
            return $redata;
        }
        else {
            return array();
        }
	}
}
