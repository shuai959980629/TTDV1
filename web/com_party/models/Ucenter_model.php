<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @ucenter用户中心
 * @author wangchuan
 * @category 2015-6-9
 * @version
 */

require_once(BASEPATH.'../com_party/libraries/ucenter/config.inc.php');
require_once(BASEPATH.'../com_party/libraries/ucenter/include/db_mysql.class.php');
require_once(BASEPATH.'../com_party/libraries/ucenter/uc_client/client.php');
class Ucenter_model extends Base_model{
		public function __construct()
		{
			parent::__construct();
		}

	//注册
	public function UcenterReg($data){
		$db = new dbstuff;
		$db->connect(UC_DBHOST, UC_DBUSER,UC_DBPW, UC_DBNAME,0);
		
		$uid = uc_user_register($data['username'], $data['password'], $data['email']);
	
        if($uid <= 0) {
		return $uid;
		/*	if($uid == -1) {
				return '用户名不合法';
			} elseif($uid == -2) {
				return '包含要允许注册的词语';
			} elseif($uid == -3) {
				return '用户名已经存在';
			} elseif($uid == -4) {
				return 'Email 格式有误';
			} elseif($uid == -5) {
				return 'Email 不允许注册';
			} elseif($uid == -6) {
				return '该 Email 已经被注册';
			} else {
				return '未定义';
			}*/
		} else {
            $username = $data['username'];
			$sql = "SELECT `username`,`password` FROM ".DZ_DBTABLEPRE."common_member WHERE `uid`={$uid}" ;
            $result = $db->query($sql);
            if ($db->num_rows($result) == 0) {
                $sql = "SELECT `username`,`password` FROM ".UC_DBTABLEPRE."members WHERE `uid`={$uid}" ;
                $result = $db->query($sql);
                $row = $db->fetch_array($result);
                //激活
                $sql = "insert into ".DZ_DBTABLEPRE."common_member set regdate='".time()."',uid='{$uid}',email='".$data['email']."',username='".$data['username']."',password='".$row['password']."',timeoffset=9999" ;
                $db->query($sql);
                $sql = "insert into ".DZ_DBTABLEPRE."common_member_status set uid='{$uid}', regip='{$_SERVER['REMOTE_ADDR']}',lastip='{$_SERVER['REMOTE_ADDR']}',lastvisit=" . time() . ", lastactivity=" . time() . ',lastpost=0, lastsendmail=0' ;
                $db->query($sql);
                $sql = "insert into ".DZ_DBTABLEPRE."common_member_profile set uid='{$uid}'" ;
                $db->query($sql);
                $sql = "insert into ".DZ_DBTABLEPRE."common_member_field_forum set uid='{$uid}'" ;
                $db->query($sql);
                $sql = "insert into ".DZ_DBTABLEPRE."common_member_field_home set uid='{$uid}' " ;
                $db->query($sql);
                $sql = "insert into ".DZ_DBTABLEPRE."common_member_count set uid='{$uid}' " ;
                $db->query($sql);
                $db->query('UPDATE '.DZ_DBTABLEPRE."common_setting SET svalue='{$data['username']}' WHERE skey='lastmember'");
                //exit;
            }
		}
		unset($db);
		return $uid;
	}
	
	//登陆
	public function UcenterLogin($data){
		$db = new dbstuff;
		$db->connect(UC_DBHOST, UC_DBUSER,UC_DBPW, UC_DBNAME,0);
        list($uid, $username, $email) = uc_get_user($data['username']);
		if (is_null($uid)){
			$_data['email'] = $data['email'];
			$_data['username'] = $data['username'];
			$_data['password'] = $data['password'];
			$_data['user_id'] = $data['user_id'];
			$uid = self::UcenterReg($_data);
			return self::UcenterLogin($data);
		}else{

			$ucsynlogin = uc_user_synlogin($uid);

		}
		//var_dump($uid);
		return $ucsynlogin;
    }

	//退出登陆
	public function UcenterLogout($uid){
		$result = uc_user_synlogout();		
		return $result;
	}
	//修改密码
	public function UcenterPwd($data){
		$db = new dbstuff;
		if($data['ignoreoldpw']==1){
		$ignoreoldpw=1;
		}else{
		$ignoreoldpw=0;
		}
		$result = uc_user_edit($data['username'], $data['oldpassword'], $data['newpassword'],$data['email'], $ignoreoldpw , "" , "") ;
		return $result;
	}
}
?>
