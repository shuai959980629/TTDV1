<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @用户模块
 * @author wangchuan
 * @category 2015-5-12
 * @version
 */
class User_model extends Base_model{
    public $table;
	public $pk = 'uid';
	//配置会员,VIP等级
	public $user_level_arr = array(
		"0"=>"普通会员",
		"1"=>"VIP1",
		"2"=>"VIP2",
	);
    public function __construct()
    {
        parent::__construct();
        $this->table = 'user';
		$this->load->model('Api_open_model', 'api_open');
    }
    //获取用户列表分页条数
	public function get_user_count($where = array()){
		$this->_search_where($where);
        return $this->db->count_all_results($this->table);
	}
	//获取用户表分页内容
	public function user_search($where = array(), $limit = 20, $offset = 0){
		$this->db->select('uid,mobile,role,email,created,last_time_logged,last_ip_logged,status');
		$this->_search_where($where);
		 if (!empty($offset)) {
            $this->db->limit($limit, $offset);
        }
        else {
            $this->db->limit($limit);
        }

        $query = $this->db->get($this->table);

        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        else {
            return array();
        }
	}
	//通过uid 数组获取用户名
	public function get_mobile_by_uidarr($data=array()){
		$this->db->select('uid,mobile');
		$this->db->where_in("{$this->table}.uid", $data);
		$query = $this->db->get($this->table);

        if ($query->num_rows() > 0) {
			//返回一维数组
			$returns = $query->result_array();
			$redata = '';
			foreach($returns as $k => $v){
				$redata[$v['uid']]=$v['mobile'];
			}
            return $redata;
        }
        else {
            return array();
        }
	}
	//通过mobile获取uid
	public function get_uid_by_mobile($mobile){
		$where = array(
            "{$this->table}.mobile" => $mobile,
        );

        $query = $this->db->get_where($this->table, $where);

        return $query->row_array();

	}

    /**
     * @获取用户信息
     */
    public function get_user_info_by_uid($uid){
        $where = array(
            "{$this->table}.uid" => $uid,
        );
        $query = $this->db->get_where($this->table, $where);
        return $query->row_array();
    }



    public function _search_where($where)
    {

        if (!empty($where['mobile'])) {
            $this->db->where("{$this->table}.mobile=", $where['mobile']);
        }
		if (!empty($where['status'])) {
            $this->db->where("{$this->table}.status=", $where['status']);
        }
        if (!empty($where['start_time'])) {
            $this->db->where("{$this->table}.created >=", strtotime($where['start_time']));
        }
        if (!empty($where['end_time'])) {
            $this->db->where("{$this->table}.created <=", strtotime($where['end_time'])+86400);
        }


        $this->db->order_by("{$this->table}.uid desc, {$this->table}.created desc");
    }
	//新增用户
	public function createNewUser($data)
	{
		$this->load->helper('string');
		$salt = random_string('alnum', 6);
		$pwd = '';
		if(key_exists('password',$data)){
			$pwd = md5_passwd($salt, $data['password']);
		}
		if(empty($data['mobile']) || empty($pwd)){
			return false;
		}		
		$user = array(
			'mobile' => $data['mobile'], //手机号码
			'email' => $data['email'], //邮箱
			'password' => $pwd,
			'salt' => $salt, //干扰码，随机字符，用于加密用户密码
			'status' => $data['status'],
			'mobile_valid'=>1, //新增注册手机认证标示；
			'created' => time(), // 注册日期
            'last_time_logged' => date('Y-m-d H:i:s', time()), //最近一次登录时间
			'last_ip_logged' => ip2long($this->input->ip_address()), //最近一次登录IP
			);
		//注册帐号
		$this->db->insert($this->table, $user);
		if ($this->db->affected_rows() > 0) {
			$uid = $this->db->insert_id();
			return $uid;
		} else {
			return false;
		}

	}
	/**
     * @用户注册
     * @param data array 用户注册的帐号信息
     * @return array
     */
    public function register($data)
    {
        try {
            $this->db->trans_begin();
            //验证用户名是否已存在
            $user = $this->mobile_exit($data['mobile']);
            $uid = $user['uid'];
            if (!$user) {
                $uid = self::createNewUser($data);
                if(!$uid){
                    return false;
                }
            }
            $user = $this->get($uid);
            //注册登录。帐号信息写入SESSION；
            $_SESSION['user'] = array(
				'uid' => $uid,
				'role' => 'member', //用户角色[0：会员，备用以后扩展会员角色]
				'salt'=>$user['salt'],
				'usr_role'=>$user['role'],
				'username' => $user['mobile'],
				'last_time_logged'=>$user['last_time_logged'],
				);
            $this->db->trans_commit();
            return $uid;
        }
        catch (exception $e) {
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                return false;
            }
        }
    }


    /**
     * @用户登陆-微信自动登录
     * @param string $openid
     * @return <boolean>
     */
    public function thirdAutoLogin($openid){
        $this->load->model('Bind_model', 'bind');
        $where = array(
            'openid'=>$openid
        );
        $bind = $this->bind->isBind($where);
        if($bind){
            $user = $this->get_user_info_by_uid($bind['uid']);
            try {
                $this->db->trans_begin();
                //修改最近登录时间和IP
                $data = array(
                    'last_time_logged' => date('Y-m-d H:i:s', time()), //最近一次登录时间
                    'last_ip_logged' => ip2long($this->input->ip_address()), //最近一次登录IP
                );
                $this->update($user['uid'], $data);
                $result = array(
                    'uid' => $user['uid'],
                    'role' => 'member', //用户角色[0：会员，备用以后扩展会员角色]
                    'salt'=>$user['salt'],
					'usr_role'=>$user['role'],
                    'username' => $user['mobile'],
                    'last_time_logged'=>$user['last_time_logged'],
                );
                $this->db->trans_commit();
                $_SESSION['user'] = $result;
                return true;
            }catch (exception $e) {
                if ($this->db->trans_status() === false) {
                    $this->db->trans_rollback();
                }
            }
        }
        return false;
    }

    /**
     * @用户登陆-微信登录
     * @param string $mobile
     * @param string $password
     * @param string $data['openid']
     * @param string $data['third_party']
     * @return <boolean>
     */
    public function thirdLogin($mobile,$password,$data){
        $user = $this->get_uid_by_mobile($mobile);
        $salt = $user['salt']; //干扰码，随机字符，用于加密用户密码
        $pwd = $user['password'];
        $mixpwd = md5_passwd($salt, $password);
        if ($mixpwd === $pwd) {
            try {
                $this->db->trans_begin();
                $this->load->model('Bind_model', 'bind');
                //微信账号绑定
                $bind['uid'] = $user['uid'] ;
                $bind['openid'] = $data['openid'];
                $bind['third_party'] = $data['third_party'];
                $res= $this->bind->userBind($bind);
                if(!$res['status']){
                    return array('status'=>false,'errors'=>$res['msg']);
                }
                //修改最近登录时间和IP
                $data = array(
                    'last_time_logged' => date('Y-m-d H:i:s', time()), //最近一次登录时间
                    'last_ip_logged' => ip2long($this->input->ip_address()), //最近一次登录IP
                );
                $this->update($user['uid'], $data);
                $result = array(
                    'uid' => $user['uid'],
                    'role' => 'member', //用户角色[0：会员，备用以后扩展会员角色]
					'usr_role'=>$user['role'],
                    'salt'=>$user['salt'],
                    'username' => $user['mobile'],
                    'last_time_logged'=>$user['last_time_logged'],
                );

                $this->db->trans_commit();
                $_SESSION['user'] = $result;
            }
            catch (exception $e) {
                if ($this->db->trans_status() === false) {
                    $this->db->trans_rollback();
                    return array('status'=>false,'errors'=>'登录失败！');
                }
            }
            return array('status'=>true,'errors'=>null,'flag'=>null);
        } else {
            return array('status'=>false,'errors'=>'账号密码错误，请重新输入！','flag'=>'password');
        }


    }





	 /**
     * @用户登陆
     * @param string $mobile
     * @param string $password
     * @param boolean  auto 0,1 是否自动登录
     * @return <boolean>
     */
    public function login($mobile, $password,$auto=0)
    {
        $user = $this->get_uid_by_mobile($mobile);
        $salt = $user['salt']; //干扰码，随机字符，用于加密用户密码
        $pwd = $user['password'];
        $mixpwd = md5_passwd($salt, $password);
        if ($mixpwd === $pwd) {
            try {
                $this->db->trans_begin();
                //修改最近登录时间和IP
                $data = array(
                    'last_time_logged' => date('Y-m-d H:i:s', time()), //最近一次登录时间
                    'last_ip_logged' => ip2long($this->input->ip_address()), //最近一次登录IP
                    );
                $this->update($user['uid'], $data);
                $result = array(
                    'uid' => $user['uid'],
                    'role' => 'member', //用户角色[0：会员，备用以后扩展会员角色]
					'salt'=>$user['salt'],
					'usr_role'=>$user['role'],
                    'username' => $user['mobile'],
					'last_time_logged'=>$user['last_time_logged'],
					);
                if ($auto) {
                    //自动登录
                    $auto_token = bin2hex(openssl_random_pseudo_bytes(64));
					//验证用户是否已经设置自动登陆
					$result_token = $this->api_open->get_row_by_uid($user['uid']);
					if(!empty($result_token) && !empty($result_token['id'])){
						$this->api_open->save(array('access_token'=>$auto_token,'expired_time'=>time()+3600*24*7),$result_token['id']);	
					}else{
						$this->api_open->save(array('access_token'=>$auto_token,'expired_time'=>time()+3600*24*7,'openid'=>$user['uid'],'uid'=>$user['uid'],'third_party'=>'tuteng'));
						
					}
					$this->load->helper('cookie');
                    set_cookie("token",$auto_token,86400*7);
                }
                $this->db->trans_commit();
                $_SESSION['user'] = $result;
            }
            catch (exception $e) {
                if ($this->db->trans_status() === false) {
                    $this->db->trans_rollback();
                    return array('status'=>false,'errors'=>'登录失败！');
                }
            }
            return array('status'=>true,'errors'=>null,'flag'=>null);
        } else {
            return array('status'=>false,'errors'=>'账号密码错误，请重新输入！','flag'=>'password');
        }
    }
	  /**
     * @修改密码
     * @param mobile string 帐号即手机号
     * @return <boolean, string>
     */
	public function mod_user_pwd($data = array()){
		if(empty($data['mobile']) || empty($data['password'])){
			return false;	
		}	
		//验证手机号码是否存在
		$user = $this->get_uid_by_mobile($data['mobile']);
		if(empty($user)){
			return false;	
		}
		//生成密码
		$pwd = md5_passwd($user['salt'], $data['password']);
		$result = parent::update($user['uid'],array('password'=>$pwd));
		if(!empty($result)){
				return $result;
		}else{
			return false;	
		}
	}
     /**
     * @判断用户帐号（手机号）是否已经注册
     * @param mobile string 帐号即手机号
     * @return <boolean, string>
     */
    public function mobile_exit($mobile)
    {
		if(empty($mobile)){
			return false;	
		}
        $this->db->select('uid')->from($this->table)->where('mobile', $mobile);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
	 /**
     * @判断注册时间是否到达指定时间
     * @param uid string 用户名
     * @return <boolean, string>
     */
	public function get_user_invite_by_created($uid,$created='2015-08-03'){
		if(empty($uid)){
			return false;	
		}
		$user = $this->get($uid);
		if($user['created']<strtotime($created)){
			return true;	
		}else{
			return false;	
		}
	}
}
