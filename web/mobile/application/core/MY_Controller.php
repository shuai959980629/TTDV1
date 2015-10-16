<?php
    class MY_Controller extends CI_Controller
    {
        public $theme          = '';
        public $lay            = 'layout';
        public $expire		   = 60;		//缓存时间

        public function __construct()
        {
            parent::__construct();
            Event::loadCfg();
            $this->output->enable_profiler(false);
			//登陆后默认保存用户个人信息
			if(!empty($_SESSION['user']) && !empty($_SESSION['user']['uid'])){
				$this->load->model('User_model', 'user');
				$this->load->model('User_identity_model', 'user_identity');
				$this->load->model('User_info_model', 'user_info');
				$this->load->model('User_bank_model', 'user_bank');
				$this->load->model('Cfg_bank_model', 'cfg_bank');
				//用户基本信息
				$_SESSION['user_info'] = (!empty($_SESSION['user_info']))?$_SESSION['user_info']:$this ->user_info->get($_SESSION['user']['uid']);
				//保存认证信息
				$_SESSION['user_identity'] = $this ->user_identity->get($_SESSION['user']['uid']);
				//保存用户银行
				$_SESSION['user_bank'] = (!empty($_SESSION['user_bank']))?$_SESSION['user_bank']:$this->user_bank-> all(array('uid'=>$_SESSION['user']['uid']));
				//列出银行卡信息
				$_SESSION['cfg_bank'] = (!empty($_SESSION['cfg_bank']))?$_SESSION['cfg_bank']:$this->cfg_bank->getAll();
				//转换银行卡号为一维数组
				foreach($_SESSION['cfg_bank'] as $cbs){
					$_SESSION['cfg_bank_arr'][$cbs['id']]=$cbs['full_name'];	
				}
				//安全等级保存到session
				$usr_safe = self::auto_usr_safe();
				$self_grade = self::usr_safe_grade();
				$_SESSION['usr_safe'] = array_merge($usr_safe,$self_grade);
			}else{
//				self::auto_login();
			}
			
			
        }
//		public function auto_login(){
//			$this->load->helper('cookie');
//			$auto_token = get_cookie("token");	
//			if(!empty($auto_token)){
//				$this->load->model('User_model', 'user');
//				$this->load->model('Api_open_model', 'api_open');
//				$result = $this->api_open->get_row_by_accesstoken($auto_token);
//				if(!empty($result) && !empty($result['uid']) && $result['expired_time']>time()){					
//					$user = $this->user->get($result['uid']);
//					 $data = array(
//						'last_time_logged' => date('Y-m-d H:i:s', time()), //最近一次登录时间
//						'last_ip_logged' => ip2long($this->input->ip_address()), //最近一次登录IP
//                    );
//					$this->user->update($user['uid'], $data);
//					$_SESSION['user'] = array(
//						'uid' => $user['uid'],
//						'role' => 'member', //用户角色[0：会员，备用以后扩展会员角色]
//						'salt'=>$user['salt'],
//						'username' => $user['mobile'],
//						'last_time_logged'=>$user['last_time_logged'],
//					);	
//				}
//			}
//		}

		/**
		 * @安全等级保存到session
		 */
		public function usr_safe_grade(){
			$uid = $_SESSION['user']['uid'];
			//查询身份证是否认证
			$user_identitys = $_SESSION['user_identity'];
			//查询个人信息，交易密码
			$user_infos = $_SESSION['user_info'];
			//获取银行卡
			$user_bank = count ($_SESSION['user_bank']);

			$speed  = 0;
			if($user_bank){
				$speed += 25;
			}
			if($user_infos['trading_password']){
				$speed += 25;
			}
			if($user_infos['extensions']){
				$speed += 25;
			}
			if( $user_identitys['status']=='allow'){
				$speed += 25;
			}
			$safe_grade = array();
			$safe_grade['speed'] = $speed;
			if($speed<=30){
				$safe_grade['grade'] = '低';
			}elseif($speed<=60){
				$safe_grade['grade'] = '中';
			}elseif($speed<=80){
				$safe_grade['grade'] = '高';
			}elseif($speed<=100){
				$safe_grade['grade'] = '高';
			}
			$safe_grade['style'] = '<div class="zhaqjb-inner bg-red" style="width:'.$speed.'%"></div>';
			return $safe_grade;
		}

		//验证实名认证，交易密码，绑定银行卡
		public function auto_usr_safe(){
			//是否实名认证
			$user_identity = $_SESSION['user_identity'];
			//默认未设置
			$usr_safe = array('ver_realname'=>false,'ver_paypassword'=>false,'ver_usbank'=>false);
			//实名认证
			if(!empty($user_identity) && $user_identity['status']=='allow'){
				$usr_safe['ver_realname'] = true;
				//更新缓存
				$_SESSION['user_identity'] = $user_identity;
			}
			//交易密码
			if(!empty($_SESSION['user_info']) && !empty($_SESSION['user_info']['trading_password'])){
				$usr_safe['ver_paypassword'] = true;	
			}
			//绑定银行卡
			if(!empty($_SESSION['user_bank'])){
				$usr_safe['ver_usbank'] = true;	
			}
			return 	$usr_safe;
		}
		/*公用发送短信
		*生成随机短信
		*$sms_name ,保存sesion名字，$sms_phone,手机号码,$sms_time,保存有效期 秒,$sms_lay,短信模板
		*/
		public function com_usr_send_sms($sms_name,$sms_phone,$sms_time,$sms_lay=array()){
			$captcha = $this->session->tempdata($sms_name);
			if(!empty($captcha['timestamp']) && time()<= $captcha['timestamp']+60){
				self::return_client(0,null,'请勿请求太快');
			}
			if(empty($captcha)){		
				$_SESSION[$sms_name] = array(
					'phone' => $sms_phone,
					'code' => mt_rand(111111, 999999),
					'timestamp' => time()
				);
				$this->session->mark_as_temp($sms_name, $sms_time); 	
				$captcha = $this->session->tempdata($sms_name);
			}	
			$sms_lay['code'] = $captcha['code'];
			$result = send_sms($captcha['phone'],$sms_lay);	
			if($result===true){
				//测试临时验证码输出
				//self::return_client(1,$this->session->tempdata($sms_name),'短信验证码发送成功');
				self::return_client(1,null,'短信验证码发送成功');			
			}else{
				self::return_client(0,null,'短信验证码发送失败');			
			}
		}
		
        protected function render($data = array(), $view = '', $content_type = 'auto', $return = FALSE)
        {
            if ($content_type == 'auto') {
                $content_type = $this->input->is_ajax_request() ? 'json' : 'html';
            }
            
            $SEO = $this->config->item('SEO');
            $SEO = $this->lay == 'layout' ? $SEO['MANAGE'] : $SEO['INDEX'];
            !isset($data['SEO']) || (isset($data['SEO']) && empty($data['SEO']['title'])) ? $data['SEO']['title'] = $SEO['title'] : '';
            !isset($data['SEO']) || (isset($data['SEO']) && empty($data['SEO']['keywords'])) ? $data['SEO']['keywords'] = $SEO['keywords'] : '';
            !isset($data['SEO']) || (isset($data['SEO']) && empty($data['SEO']['description'])) ?  $data['SEO']['description'] = $SEO['description'] : '';
            
            switch ($content_type) {
                case 'json':
                    if ($return === FALSE) {
                        $this->output->enable_profiler(FALSE);
                        $this->output
                                ->set_status_header(200)
                                ->set_content_type('application/json', 'utf-8')
                                ->set_output(json_encode($data));
                    }
                    else {
                        return json_encode($data);
                    }
                    break;
                case 'html':
                default:
                    if (empty($view)) {
                        $view = $this->router->class . '/' . $this->router->method . '.php';
                    }
                    $data['_COMMON'] = $this->config->item('common');
                    return $this->layout->load($data, $this->lay, $view, $this->theme, $return);
                    break;
            }
        }
		 /**
         * 返回客户端信息通用函数
         * @param number $status 返回状态
         * @param string $data	包含的数据
         * @param string $msg	状态说明
         */
        public function return_client($status = 0, $data = null, $msg = null)
        {
            $requesttype = $this->input->is_ajax_request();
            if(ENVIRONMENT !== 'production' || ($requesttype && strtolower($_SERVER['REQUEST_METHOD']) == 'post')){
                header('Content-type: application/json;charset=utf-8');
                $resp = array(
                    'status' => $status,
                    'data' => empty($data) ? null : $data,
                    'msg' => empty($msg) ? null : $msg,
                    'time' => date('Y-m-d H:i:s', time()));//microtime(true) - $starttime);
                $json = json_encode($resp);
                die($json);
            }
        }
    }
