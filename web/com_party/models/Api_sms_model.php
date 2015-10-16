<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @电信189短信发送模块
 * @author wangchuan
 * @category 2015-8-14
 * @version
 */
class Api_sms_model extends Base_model{
	//应用APIID appsecret 配置
	public $appid = "322929100000245560";
	public $appsecret = "4493877760b25f9e4e9e16ee25a18214";
	public $redirectUri = "http://www.tt1v2.com/authentication/redirect.php";
	public $authorizeAPI = "https://oauth.api.189.cn/emp/oauth2/v3/authorize";
	public $tokenAPI = "https://oauth.api.189.cn/emp/oauth2/v3/access_token";
	
    public function __construct()
    {
        parent::__construct();
    }
	/**
	 * curl方法GET POST
	 */
	//post方式请求
	public function curl_post($url='', $postdata='', $options=array()){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		/*解决SSL certificate problem: self signed certificate in certificate chain*/
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($options)){
			curl_setopt_array($ch, $options);
		}
		$data = curl_exec($ch);
		if (curl_errno($ch)) {
			return  'Errno'.curl_error($ch);//捕抓异常
		}
		curl_close($ch);
		return $data;
	}
	
	//get 方式请求
	public function curl_get($url='', $options=array()){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		if (!empty($options)){
			curl_setopt_array($ch, $options);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	//获取access_token令牌；
	public function get_access_token(){
		$appid = $this->appid;
		$appsecret = $this->appsecret;
		$tokenAPI = $this->tokenAPI;
		$grant_type = 'client_credentials';
		$data = "app_id={$appid}&app_secret={$appsecret}&grant_type={$grant_type}";
		$result_josn = self::curl_post($tokenAPI,$data);	
		$result = json_decode($result_josn, true);
		if(!empty($result) && $result['res_code']==0){
			return $result;	
		}else{
			log_message('error', '获取短信模板令牌access_token失败','sms_access_token');
			return false;	
		}
	}
	//发送模板短信$mobile 手机号码 ，$acceptor_tel 模板ID，$param模板参数
	public function send_template_sms($mobile,$template_id,$param=array()){
		$time = date("Y-m-d H:i:s");
		$appid = $this->appid;
		$this->load->driver('cache');
		$access_token = $this->cache->memcached->get("sms_open189_access_token");
		if(empty($access_token)){
			$result_access_token = self::get_access_token();
			if(!empty($result_access_token) && !empty($result_access_token['access_token'])){
				$this->cache->memcached->save('sms_open189_access_token',$result_access_token['access_token'],$result_access_token['expires_in']-5);
				$access_token = $result_access_token['access_token'];	
			}else{
				return false;	
			}
		}
		if(empty($mobile) || empty($template_id)){
			return false;
		}
		$param = json_encode($param);
		$send = "acceptor_tel={$mobile}&template_id={$template_id}&template_param=".$param."&app_id={$appid}&access_token={$access_token}&timestamp={$time}";
		$result = self::curl_post("http://api.189.cn/v2/emp/templateSms/sendSms", $send);
		$result_arr = json_decode($result, true);
		if(!empty($result_arr) && $result_arr['res_code']==0){
			return true;	
		}else{
			log_message('error', $result,'sms_access_token');
			return false;	
		}
	}

}
