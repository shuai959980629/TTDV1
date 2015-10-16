<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 * @package		Libraries
 * @author		Glen.luo
 * @since		Version 1.0
 */

// ------------------------------------------------------------------------

/**
 * 为图片添加水印.
 *
 * @access	public
 * @param	string 图片存放路径
 * @return	string
 */
if ( ! function_exists('create_wm_image'))
{
    function create_wm_image($file_path, $wm_pos = array('bottom', 'right'))
    {
        if (file_exists($file_path) === FALSE) {
            return FALSE;
        }

        $wm_font_path = realpath(BASEPATH . '../frameworks/fonts/zhs.ttf');
        if (file_exists($wm_font_path) === FALSE) {
            log_message('error', "not found font file: {$wm_font_path}");
            return FALSE;
        }

        $CI = & get_instance();

        $config['image_library'] = 'GD2';
        $config['source_image'] = $file_path;
        $config['wm_text'] = 'Copyright 2014 - xxx';
        $config['wm_type'] = 'text';
        $config['quality'] = 100;
        $config['dynamic_output'] = FALSE;
        $config['wm_font_path'] = $wm_font_path;
        $config['wm_font_size'] = '16';
        /*$config['wm_font_color'] = '018077';*/
        $config['wm_font_color'] = 'b2b2b2';
        $config['wm_vrt_alignment'] = $wm_pos[0];
        $config['wm_hor_alignment'] = $wm_pos[1];
        $config['wm_hor_offset'] = 10;
        $config['wm_vrt_offset'] = 2;
        /*$config['wm_padding'] = '6';*/

        /*$config['image_library'] = 'gd2';
        $config['source_image'] = $file_path;
        $config['dynamic_output'] = FALSE;
        $config['quality'] = 100;
        $config['wm_type'] = 'overlay';
        $config['wm_padding'] = '0';
        $config['wm_vrt_alignment'] = $wm_pos[0];
        $config['wm_hor_alignment'] = $wm_pos[1];
        $config['wm_hor_offset'] = 6;
        $config['wm_vrt_offset'] = 6;
        $config['wm_overlay_path'] = $wm_overlay_path;
        $config['wm_opacity'] = 90;
        $config['wm_x_transp'] = '4';
        $config['wm_y_transp'] = '4';*/
        $CI->load->library('image_lib');
        $CI->image_lib->initialize($config);
        $result = $CI->image_lib->watermark();
        if (!$result) {
            log_message('error', strip_tags($CI->image_lib->display_errors()));
        }
        $CI->image_lib->clear();
    }
}

/**
 * 标题截取.
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('get_short_title'))
{
    function get_short_title($title, $len = 10, $suffix = '...')
    {
        $title = preg_replace('/\s|&nbsp;+/m', '', $title);

        if (mb_strlen($title, 'utf-8') > $len) {
            return mb_substr($title, 0 , $len, 'utf-8') . $suffix;
        }

        return $title;
    }
}

/**
 * 加载组件片断.
 *
 * @access	public
 * @param	string 组件名称
 * @param	array  组件所需数据
 * @return	string
 */
if ( ! function_exists('load_widget'))
{
    function load_widget($template, $data = array())
    {
        if (empty($template)) {
            return '';
        }

        $CI = & get_instance();
        return $CI->load->view($template, $data, TRUE);
    }
}

/**
 * 得到用于展示的时间.
 *
 * @access	public
 * @return	string
 */
if ( ! function_exists('get_show_time'))
{
    function get_show_time($time, $format = 'date')
    {
		$result = '';
		$current_time = time();
		$sub_time = $current_time - $time;
		if(60 > $sub_time)
		{
			$result = '刚刚';
		}
		if(60 <= $sub_time && 3600 >= $sub_time)
		{
			$result = floor($sub_time/60);
			$result = $result.' 分钟前';
		}
		if(3600 <= $sub_time && 86400 >= $sub_time)
		{
			$result = floor($sub_time/3600);
			$result = $result.' 小时前';
		}
		if(86400 < $sub_time)
        {
            if ($format == 'date') {
			    $result = date('m月d日', $time);
            }
            else {
			    $result = floor($sub_time/86400).'天前';
            }
		}
		return $result;
    }
}

/**
 * 广告组件.
 *
 * @access	public
 * @param	int	the ad position id.
 * @return	array
 */
if ( ! function_exists('ad_widget') )
{

	function ad_widget($id = 0)
    {
        $id = (int) $id;

        if (empty($id)) {
            return '';
        }

        $CI = & get_instance();

        $CI->load->model('ad_position_model');
        $position = $CI->ad_position_model->get($id);
        if (empty($position)) {
            return '';
        }

        $CI->load->model('ad_model');
        $ads = $CI->ad_model->get_ads_by_position($id);
        if (empty($ads)) {
            return '';
        }

        foreach ($ads as $k => $v) {
            if ($v['enabled'] != 1) {
                unset($ads[$k]);
            }
            elseif (time() < $v['start_time']) {
                unset($ads[$k]);
            }
            elseif (time() > $v['end_time']) {
                unset($ads[$k]);
            }
            /*elseif($v['media_type'] == 'img' || $v['media_type'] == 'flash') {
                $ads[$k]['content'] = $ads[$k]['content'];
            }*/
        }

        $data = array(
            'ad_entries' => array_values($ads)
        );

        $_tempate_file = VIEWPATH . "widgets/adp_{$position['id']}.php";

        $CI->load->helper('file');
        $_tempate_file_info = get_file_info($_tempate_file);

        if ($_tempate_file_info === FALSE || $_tempate_file_info['date'] < strtotime($position['created'])) {
            $result = write_file($_tempate_file, $position['template']);
            if ($result === FALSE) {
                return NULL;
            }
        }

        $ads_html = $CI->load->view("widgets/adp_{$position['id']}", $data, TRUE);
        return $ads_html;
    }
}

/**
 * 加载指定角色的用户权限.
 *
 * @access	public
 * @param	array
 * @return	string
 */
if ( ! function_exists('load_priv_by_role'))
{
    function load_priv_by_role($role)
    {
        $CI = & get_instance();

        $priv = array();

        $purview = $CI->config->item('purview');
        if ($purview === FALSE) {
            log_message('error', 'config/priv.php权限配置文件取不到值');
            return NULL;
        }

        $role_cfg = $CI->config->item('role');

        if ($role_cfg === FALSE || array_key_exists($role, $role_cfg) === FALSE) {
            log_message('error', "不存在的用户角色:{$role}，请检查config/role.php角色配置文件");
            return NULL;
        }

        if (count($role_cfg[$role]['modules']) == 1 && $role_cfg[$role]['modules'][0] == '*' ) {
            $priv = array_keys($purview);
        }
        else {
            $priv = array_values($role_cfg[$role]['modules']);
        }

        return $priv;
    }
}

/**
 * 生成唯一主键
 *
 * @access	public
 * @param	null
 * @return	int 63bits
 */
if ( ! function_exists('unique_id'))
{
    function unique_id()
    {
        $CI        = & get_instance();

        $tickets_db = $CI->load->database('tickets', TRUE);

        $query = $tickets_db->query('REPLACE INTO tickets64 (stub) VALUES ("a")');

        $ticket_id = NULL;

        if ($query) {
            $ticket_id = $tickets_db->insert_id();
        }

        $tickets_db->close();
        return $ticket_id;
    }
}
/**
 * 用户密码加密算法
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if (!function_exists('md5_passwd')) {
    function md5_passwd($salt, $password)
    {
        return md5(md5($password).$salt);
    }
}
/**
 * 获取客户端请求地IP
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        if (getenv('HTTP_CLIENT_IP') and strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') and strcasecmp(getenv('HTTP_X_FORWARDED_FOR'),
        'unknown')) {
            $onlineip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') and strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $onlineip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] and
        strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $onlineip = $_SERVER['REMOTE_ADDR'];
        }
        preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $onlineip, $match);
        return $onlineip = $match[0] ? $match[0] : 'unknown';
    }
}

/**
 * @根据IP地址获取所在城市
 * @access	public
 * @return
 * @author zhoushuai
 */
if (!function_exists('GetIpLookup')) {
    function GetIpLookup($ip = ''){
        if(empty($ip)){
            $ip = get_client_ip();
        }
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if(empty($res)){ return false; }
        $jsonMatches = array();
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        if(!isset($jsonMatches[0])){ return false; }
        $json = json_decode($jsonMatches[0], true);
        if(isset($json['ret']) && $json['ret'] == 1){
            $json['ip'] = $ip;
            unset($json['ret']);
        }else{
            return false;
        }
        return $json;
    }
}



/**
 * 检查身份证号码是否合法
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if (!function_exists('isIdCard')) {
	function isIdCard($vStr){
    $vCity = array(
        '11','12','13','14','15','21','22',
        '23','31','32','33','34','35','36',
        '37','41','42','43','44','45','46',
        '50','51','52','53','54','61','62',
        '63','64','65','71','81','82','91'
    );

    if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;

    if (!in_array(substr($vStr, 0, 2), $vCity)) return false;

    $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
    $vLength = strlen($vStr);

    if ($vLength == 18)
    {
        $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
    } else {
        $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
    }

    if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;

    /*if ($vLength == 18)
    {
        $vSum = 0;

        for ($i = 17 ; $i >= 0 ; $i--)
        {
            $vSubStr = substr($vStr, 17 - $i, 1);
            $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
        }

        if($vSum % 11 != 1) return false;
    }*/

    $checkBit = array(1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2);

    if ($vLength == 18)
    {
        $vSum = 0;

        for ($i = 17 ; $i > 0 ; $i--)
        {
            $vSubStr = substr($vStr, 17 - $i, 1);
            $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr));
        }

        if(!isset($checkBit[$vSum % 11])) return false;
    }

    return true;
	}
}
/**
 * 发送短信
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if (!function_exists('send_sms')) {
	function send_sms($mobile,$data=array()){
		//获取配置
		$CI = & get_instance();
		$CI ->load->config('sms_layout');
		if(empty($data['template'])){
			return false;
		}

        //发送微信消息模板
        $wx_cfg_way = $CI->config->item('switch_wx_tmp');
        if( isset($wx_cfg_way) && key_exists($data['template'],$wx_cfg_way)){
            $method = $wx_cfg_way[$data['template']];
            if(empty($method)){
                log_message('error', '获取微信消息模板['.$data['template'].']失败或不存在，请检查application/config/sms_layout.php');
            }else{
                $CI->load->model('Wxtemplate_msg_model','wxtmp');
                $data['mobile'] = $mobile;
                $data['method'] = $method;
                $CI->wxtmp->sendWxTemple($data);
            }
        }else{
            log_message('error', '获取微信消息模板['.$data['template'].']失败或不存在，请检查application/config/sms_layout.php');
        }


        //投资成功提示等需要判断用户是否有短信权限
        $charge_template = array('tender_auto_template', 'tender_hand_template', 'repay_capital_template');
        if(in_array($data['template'], $charge_template) && time() > strtotime(date('2015-10-11'))){
            if(!bag_is_expires($mobile, 'sms')){
                log_message('error', $mobile.'没有权限', 'sms_test');
                return true;
            }
        }

        //log_message('error', '有权限', 'sms_test');
        //发送短息消息
        $sms_cfg_tem = $CI->config->item('sms_content');
		if(empty($sms_cfg_tem[$data['template']])){
			log_message('error', '获取不到模板内容，请检查application/config/sms_layout.php');
			return false;
		}
		$sys_content = $sms_cfg_tem[$data['template']];
		foreach($data as $k=>$v){
			if($k!='template'){
				$sys_content = str_replace($k,$v,$sys_content);
			}
		}
		//选择发送通道
		$sms_cfg_way = $CI->config->item('switch_sms_way');
		switch($sms_cfg_way[$data['template']]){
			case "yunxin_sms":
				$sms_api = 'http://api.sms.cn/mt/?uid=zdxrchina';
				$sms_api .= '&pwd=5bb4c0b757de293564aeb7c4bc0beb62';
				$sms_api .= "&mobile={$mobile}";
				$sms_api .= "&content=" . $sys_content;
				$sms_api .= "&encode=utf8";
				$result = file_get_contents($sms_api);
				if (strpos($result, 'stat=100') !== FALSE ) return true; else return false;
				break;
			case "open189_sms":
				$CI->load->model('Api_sms_model');
				//获取用户短信模板ID
				$sms_template_id = $CI->config->item('sms_template_id');
				$item_sms_template_id = $sms_template_id[$data['template']];
				unset($data['template']);
				$result = $CI->Api_sms_model->send_template_sms($mobile,$item_sms_template_id,$data);
				if(!empty($result) && $result==true)return true;else return false;
				break;	
			case "chuangnan_sms":
				$url='http://222.73.117.158/msg/HttpBatchSendSM?'; 
				$CI->load->model('Api_sms_model');
				$send = "account=qhtthl&pswd=Qhtthl123&mobile={$mobile}&msg={$sys_content}";
				$result = $CI->Api_sms_model->curl_post($url,$send);
				log_message('error', 'parm:'.$send.'result'.$result,'sms_send_logs');
				if (strpos($result, ',0') !== FALSE ) return true; else return false;
			break;
		}//switch 结束
	}//dend_sms 方法结束
}


/**
 * @author houxijian
 * @access	public
 * 短信包监控是否过期
 */
if(!function_exists('bag_is_expires')){
    function bag_is_expires($mobile, $tob)
    {
        $CI = & get_instance();

        $CI->load->model('User_model', 'user');
        $CI->load->model('User_bag_model', 'user_bag');

        $user = $CI->user->getWidgetRow(array("mobile"=>$mobile));
        return $CI->user_bag->is_expires($user['uid'], $tob);
    }
}

/**
 * @author zhoushuai
 * @access	public
 * @https请求（支持GET和POST）
 * @param url string 请求的地址
 * @param data  <array,xml> 发送的数据
 */
if(!function_exists('https_request')){
    function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}

/**
 * @author 生成15位的排名时间
 */
if(!function_exists('rank_time')){
    function rank_time()
    {
        return array_sum(explode(' ', microtime())) * 100000;;
    }
}

/**按天到期还款
 *
 * $period:天数 $account：金额 $apr:年化率（乘以100后的）
 *
 */
if(!function_exists('EqualDayEnd')){
    function EqualDayEnd ($period, $account, $apr, $type=''){
        if($period == '' || $account == ''||  $apr == ''){
            return FALSE;
        }

        /*$interest = $day_apr*$period*$account;	*/
        bcscale(3);
        $interest_total = round(($period / 30 * $apr / 100 * $account / 12), 3);
        $interest_day = bcmul($apr / 100 * $account / 360, 1);
        //$interest = bcmul($interest_day , $period);

        if (isset($type) && $type=="all"){
            $_result_all['account_total'] =   round($account + $interest_total,3);
            $_result_all['interest_total'] =  round($interest_total,3);
            $_result_all['capital_total'] =  round($account,3);
            $_result_all['repay_month'] =  round($account + $interest_total,3);
            /*$_result_all['month_apr'] = round($month_apr*100,2);*/
            /*$_result_all['interest_day'] = round($interest_day,2);	// 每天利率*/
            $_result_all['interest_day'] = $interest_day;	// 每天利率
            return $_result_all;
        }else{
            $_result[0]['account_all'] = round($interest_total+$account,3);
            $_result[0]['account_interest'] = round($interest_total,3);
            $_result[0]['account_capital'] = round($account,3);
            $_result[0]['account_other'] = round($account,3);
            $_result[0]['repay_month'] = round($interest_total+$account,3);
            $_result[0]['repay_time'] = strtotime("+".$period." day");
            /*$_result[0]['interest_day'] = round($interest_day,2);*/
            $_result[0]['interest_day'] = $interest_day;
            return $_result;
        }
        return $_result;
    }
}

//等额本息法
//贷款本金×月利率×（1+月利率）还款月数/[（1+月利率）还款月数-1]
//a*[i*(1+i)^n]/[(1+I)^n-1]
//（a×i－b）×（1＋i）
if(!function_exists('EqualMonth')){
    function EqualMonth ($period, $account, $year_apr, $time, $type=''){
        $time = $time ? $time : time();
        $month_apr = $year_apr/(12*100);
        $_li = pow((1+$month_apr),$period);
        if ($account<0) return;
        $repay_account = round($account * ($month_apr * $_li)/($_li-1),3);//515.1

        $_result = array();
        //$re_month = date("n",$borrow_time);
        $_capital_all = 0;
        $_interest_all = 0;
        $_account_all = 0.00;
        for($i=0;$i<$period;$i++){
            if ($i==0){
                $interest = round($account*$month_apr,3);
            }else{
                $_lu = pow((1+$month_apr),$i);
                $interest = round(($account*$month_apr - $repay_account)*$_lu + $repay_account,3);
            }

            //echo $repay_account."<br>";
            //防止一分钱的问题
            if ($i==$period-1)
            {
                $capital = $account - $_capital_all;
                $interest = $repay_account-$capital;
            }else{
                $capital =  $repay_account - $interest;
            }

            //echo $capital."<br>";
            $_account_all +=  $repay_account;
            $_interest_all +=  $interest;
            $_capital_all +=  $capital;

            $_result[$i]['account_all'] =  round($repay_account,3);
            $_result[$i]['account_interest'] = round( $interest,3);
            $_result[$i]['account_capital'] =  round($capital,3);
            $_result[$i]['account_other'] =  round($repay_account*$period-$repay_account*($i+1),3);
            $_result[$i]['repay_month'] =  round($repay_account,3);
            /*$_result[$i]['repay_time'] = get_times(array("time"=>$time,"num"=>$i+1));*/
            $_result[$i]['repay_time'] = $time + ($i+1) * 30  * 86400;
        }
        if ($type=="all"){
            $_result_all['account_total'] =  round($_account_all,3);
            $_result_all['interest_total'] =  round($_interest_all,3);
            $_result_all['capital_total'] =  round($_capital_all,3);
            $_result_all['repay_month'] =  round($repay_account,3);
            $_result_all['month_apr'] = round($month_apr*100,3);
            return $_result_all;
        }
        return $_result;
    }
}
/**
 * 手机号码部分隐藏
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('hideMobile')){
    function hideMobile($mobile){
		return substr_replace($mobile,'******',3,6);
	}
}
/**
 * 银行卡部分隐藏
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('hideBank')){
    function hideBank($bank){
		return substr_replace($bank,'&nbsp;****&nbsp;****&nbsp;*',4,-4);
	}
}
/**
 * 身份证号码部分隐藏
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('hideIdcard')){
    function hideIdcard($idcard,$type='long'){
		if($type=='short'){
			return substr_replace($idcard,'****',3,-2);
		}else{
			return substr_replace($idcard,'***********',3,-4);
		}
	}
}
/**
 * 生成推荐码
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
 if(!function_exists('uencodeInvite')){
    function uencodeInvite($uid = 0){
		$uid = intval($uid);
		if (empty($uid)) {
			return 0;
		}
		return (~$uid + 10000000);
	}
}
/**
 * 解密推荐码
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('udecodeInvite')){
    function udecodeInvite($uid = 0){
		$uid = intval($uid);
		if (empty($uid)) {
			return 0;
		}
		return ~($uid - 10000000);
	}
}
/**
 * 获取网站目录路径
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
  if ( ! function_exists('base_url'))
 {
	 function base_url($uri = "")

	 {
		 $CI =& get_instance();
		 return $CI->config->base_url($uri);
	 }
 }

/**
 * 借款信息图标
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('borrow_icon')){
    function borrow_icon($borrow, $page='', $param = false){

        if(isset($borrow['member_flag']) && $borrow['member_flag'] == 'try'){
            return '<div class="icon-list2 float-left"><p class="float-left iconbg1"><i class="icon iconfont icon-zhubaoshipin "></i>特权标</p></div>';
        }
        $html = $param ? '<div class="icon-list2 float-left mt5">' : '';

        if($borrow['uid'] > 0 && $borrow['parent_id'] > 0){
            $html .= '<p class="float-left mr10 iconbg3 pointer zhuanrangHoverBtn" style="position: relative;"><em class="model-tishi zhuanrangHoverem" style="top: -34px;left: 0"></em><i class="icon iconfont icon-yijianzhifuline64"></i> 转让</p>';
        }

        if(isset($borrow['deal_flag']) && $borrow['deal_flag'] == 'mortgage'){
            $html .= '<p class="float-left mr10 iconbg2"><i class="icon iconfont icon-choiceness"></i> 抵押</p>';
        }

        if(isset($borrow['deal_flag']) && $borrow['deal_flag'] == 'pledge'){
            $html .= '<p class="float-left mr10 iconbg1"><i class="icon iconfont icon-zhanghaoanquan"></i> 质押</p>';
        }

        if ($page != 'none') {
            if($borrow['member_flag'] == 'novice'){
                $html .= '<p class="float-left mr10 iconbg11 pointer xinshouJianjieBtn" style="position: relative;"><em class="model-tishi xinshouem" style="top: -34px;left: -40px"><i class="icon-arrowdown"></i>投资总额少于2万者可投</em><i class="icon iconfont icon-new"></i> 新手标</p>';
            }

	        if($borrow['tender_money_max'] < $borrow['amount']){
	            $html .= '<p class="float-left mr10 iconbg12"><i class="icon iconfont icon-choiceness"></i> 限额';
	            if($page == 'borrow_detail'){
	                $html .= ' ￥'.$borrow['tender_money_max'].'/人';
	            }
	            $html .= '</p>';
	        }

	        if($borrow['uid'] == 0 && $borrow['parent_id'] > 0){
	            $html .= '<p class="float-left mr10 iconbg13"><i class="icon iconfont icon-liuliang"></i> 续借</p>';
	        }
        }

        $html .= $param ? '</div>' : '';

        return $html;
    }
}
/**
 * 借款信息图标
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('borrow_tag')){
    function borrow_tag($borrow){
        if($borrow['member_flag'] == 'try'){
            return '<i class="icon-tequan"></i>';
        }

        if($borrow['member_flag'] == 'novice' && (strtotime("-2 hours") < $borrow['verified_time'] || !$borrow['verified_time'])){
            return '<i class="icon-xinshou"></i>';
        }

        if($borrow['status'] == 'published'){
            return '<i class="icon-time3"></i>';
        }
    }
}

/**
 * 借款信息图标(手机端)
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('mobile_borrow_tag')){
    function mobile_borrow_tag($borrow){
        if($borrow['status'] == 'published'){
            if(isset($borrow['is_first_published'])){
                return '<span class="iconbg13"><i class="icon iconfont icon-xitongtuisong"></i>即将发标</span>';
            }else{
                return '<span class="iconbg12"><i class="icon iconfont icon-time"></i>预告</span>';
            }
        }
        return;
    }
}

/**
 * 时间转化（某个时间在当前时间之前（天小时分钟秒））
 * @access	public
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('time_to_ago')){
    function time_to_ago($time){
        if($time < 60){
            $ago = $time.'秒';
            return $ago;
        }else{
            $time = round($time / 60, 0);
        }
        if($time < 60){
            $ago = $time.'分钟';
            return $ago;
        }else{
            $time = round($time / 60, 0);
        }
        if($time < 60){
            $ago = $time.'小时';
            return $ago;
        }else{
            $time = round($time / 60, 0);
        }
        $ago = $time.'天';
        return $ago;
    }
}

/**
 * 计算两个时间戳之间相差的日时分秒
 * $begin_time 开始时间戳
 * $end_time 结束时间戳
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('time_diff')){
    function time_diff($begin_time, $end_time){
        if($begin_time < $end_time){
            $starttime = $begin_time;
            $endtime = $end_time;
        }else{
            $starttime = $end_time;
            $endtime = $begin_time;
        }

        //计算天数
        $timediff = $endtime-$starttime;
        $days = intval($timediff / 86400);
        //计算小时数
        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);
        //计算分钟数
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        //计算秒数
        $secs = $remain % 60;
        $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
        return $res;
    }
}

/**
 * 计算两个时间戳之间相差的日时分秒
 * $begin_time 开始时间戳
 * $end_time 结束时间戳
 * @return	<string>
 * @author wangchuan
 */
if(!function_exists('format_time_diff')){
    function format_time_diff($begin_time, $end_time){
        $res = time_diff($begin_time, $end_time);
        $format_str = $res['day'] > 0 ? $res['day'].'天': '';
        $format_str .= $res['hour'] > 0 ? $res['hour'].'小时': '';
        $format_str .= $res['min'] > 0 ? $res['min'].'分钟': '';
        $format_str .= $res['sec'].'秒';
        return $format_str;
    }
}

/**
 * 获取发标金额总数 [统计规则：状态 = 'fills' + 'repayment' + 'done' + 'early']
 *
 * @access	public
 * @author  LEE
 * @return	number
 */
if ( ! function_exists('getBorrowAmount') )
{

	function getBorrowAmount()
    {
        $CI = & get_instance();

        $CI->load->model('borrow_model');

        $where = array(
    		'sum'	=> 'amount',
    		'in' 	=> array('status' => array('fills', 'repayment', 'done', 'early'))
    	);
    	$total = $CI->borrow_model->getWidgetRow($where);

        return !empty($total['amount']) ? $total['amount'] : 0;
    }
}

/**
 * 获取全部总收益 [统计规则：状态 = 'done']
 *
 * @access	public
 * @author  LEE
 * @return	number
 */
if ( ! function_exists('getTenderInterestAmount') )
{

	function getTenderInterestAmount()
    {
        $CI = & get_instance();

        $CI->load->model('tender_log_model');

        $where = array(
    		'sum'	 => 'recover_total_interest',
    		'status' => 'done'
    	);
    	$total = $CI->tender_log_model->getWidgetRow($where);

        return !empty($total['recover_total_interest']) ? $total['recover_total_interest'] : 0;
    }
}

/**
 * 页脚项目 [关于我们、相关政策、法律法规]
 * 设置方法：在管理后台资讯管理设置分类和具体内容
 *
 * @access	public
 * @author  LEE
 * @return	number
 */
if ( ! function_exists('getArticleFun') )
{

	function getArticleFun($data = array(), $i = 0, $title = array())
    {
    	if (empty($title)) {
    		$title = array(
				0 => array(
					'name'  => '关于我们',
					'limit' => 4
				),
				1 => array(
					'name'  => '相关政策',
					'limit' => 4
				),
				2 => array(
					'name'  => '法律法规',
					'limit' => 4
				),
				3 => array(
					'name'  => '图腾贷分公司',
					'limit' => 5
				),
			);
    	}

    	$CI = & get_instance();

        $CI->load->model('article_cat_model');
        $CI->load->model('article_model');

    	$rule  = array('title' => $title[$i]['name']);
    	$acate = $CI->article_cat_model->getWidgetRow($rule);

    	if (!empty($acate)) {
    		$where = array(
	    		'cols'	 	=> array('id', 'cat_id', 'title', 'url_route', 'cover_img', 'summary'),
	    		'cat_id' 	=> $acate['id'],
	    		'order'		=> array('created' => 'desc'),
	    		'limit'  	=> $title[$i]['limit']
	    	);

	    	$result = $CI->article_model->getWidgetRows($where);
	    	if (!empty($result)) {
		    	foreach ($result as &$value) {
		    		if (empty($value['url_route'])) {
		    			if (!empty($acate['detail_template'])) {
		    				$value['url_route'] = '/' . $acate['detail_template'] . '/' . $value['id'];
		    			} else {
		    				$value['url_route'] = '/article/detail/' . $value['id'];
		    			}
		    		}
		    	}
		    	unset($value);
		    	if (!empty($title[$i]['alias'])) {
		    		$data[$title[$i]['alias']] = $result;
		    	} else {
		    		$data[$title[$i]['name']] = $result;
		    	}
	    	}
    	}

    	$i++;

    	return $i >= count($title) ? $data : getArticleFun($data, $i, $title);
    }
}

/**
 * 写入管理员操作日志.
 *
 * @access	public
 * @return	null
 */
if ( ! function_exists('admin_log'))
{
	function admin_log($msg)
    {
        $CI = & get_instance();
        $data = array(
            'uid'       => $_SESSION['user']['uid'],
            'action'    => $msg,
            'ip'        => ip2long($CI->input->ip_address()),
            'model'     => $CI->router->class,
        );
        $CI->load->model('admin_log_model', 'admin_log');
        $CI->admin_log->write($data);
	}
}

/**
 * 还款日志
 *
 * @access	public
 * @return	null
 */
if ( ! function_exists('write_repay_log'))
{
    function write_repay_log($msg)
    {
        $filename = 'repay_log';
        log_message('error', $msg, $filename);
    }
}

/**
 * 字符串切取
 *
 * @param 	string	$str		//被切割字符串
 * @param 	integer $strlen		//切割长度
 * @param 	boolean	$other		//切割后是否添加。。。
 * @access	public
 * @author  LEE
 * @return	string
 */
if ( ! function_exists('output'))
{
    function output($str, $strlen = 30, $other = true)
	{
		for ($i = 0; $i < $strlen; $i++) {

			$temp_str = substr($str, 0, 1);
			$ord  	  = ord($temp_str);

			if ($ord >= 192 && $ord <= 247) {
				$new_str[]  = substr($str, 0, 3);
				$str 		= substr($str, 3);
			} else {
				$new_str[] 	= substr($str, 0, 1);
				$str 		= substr($str, 1);
			}
		}
		$rstr = join($new_str);

		if (strlen($rstr) > $strlen && $other) $rstr .= '...';

		return $rstr;
	}
}

/**
 * 数字格式
 *
 * @param 	string	$number		//数字
 * @param 	integer $strlen		//小数位长度
 * @param 	boolean	$round		//是否进行四舍五入
 * @param 	boolean	$format		//格式化[科学计数法，默认不开启]
 * @access	public
 * @author  LEE
 * @return	integer | float
 */
if ( ! function_exists('numberFormat'))
{
    function numberFormat($number, $strlen = 0, $round = false, $format = false)
	{
		if ($number === null) {
			$number = 0;
		}

		if (!is_numeric($number)) {
			return $number;
		}

		if (!strpos($number, '.')) {
			if ($strlen > 0 && !$round) {
				$decimal = str_pad("0", $strlen, "0");
				$number  = $number . '.' . $decimal;
			}
			
			$format && $number = number_format($number);
			return (string) $number;
		}

		$num  = $decimal = '';

		$nums = explode('.', $number);
		$lens = $strlen ? $strlen : strlen($nums[1]);

		$decimal = substr($nums[1], 0, $lens);

		if (!$round && strlen($decimal) < $strlen) {
			$decimal = (string) str_pad($decimal, $strlen, "0");
			$num 	 = $nums[0] . '.' . $decimal;
		} else {
			$num 	 = $nums[0] . '.' . $decimal;
			$round && $num = round((float) $num, $strlen);
			!$strlen && $num = (float) $num;
		}
		
		$format && $num = number_format($num);

		return (string) $num;
	}
}

/**
 * 用户的综合数据 [用户总资金、用户可用资金、用户总待收资金、排名、排队总人数、排在该用户前的资金、排队总资金、正在排队用户[一月标、二月标、三月标、抵押标、不限标]]
 *
 * @param  integer 	$uid		//用户UID [必填]
 * @param  string 	$expire		//过期时间，使用SESSION存储的数据，单位秒 [非必填]
 * @param  string 	$rank_time	//用户当前的排名时间戳	[自动获取]
 * @param  array 	$rule		//查询条件 [自动获取]
 * @param  array 	$data		//数据集	[自动获取]
 * @param  integer 	$i			//计数器	[自动获取]
 * @author LEE
 * @copyright 20150821
 * @return array
 */
if ( ! function_exists('userComplexData'))
{
	function userComplexData($uid, $expire = 0, $rank_time = '', $rule = array(), $data = array(), $i = 0)
	{
		//启用SESSION记录 时间由程序员自行控制
//		if (!$i && isset($_SESSION['complex']) && isset($_SESSION['complex']['expire']) 
//			&& (time() - $_SESSION['complex']['expire']) < $expire) {
//			return $_SESSION['complex'];
//		}
		
		$CI = & get_instance();
		$CI->load->model('tender_auto_seting_model', 'tender_auto_seting');
		
		return $CI->tender_auto_seting->getAutobid($uid, $expire);
		
//		$CI->load->model('api_fund_model'	 	   , 'api_fund');
//		if (empty($uid)) {
//			return false;
//		}
//		
//		//当前排名
//		if (empty($rank_time)) {
//			$CI->tender_auto_seting->pk = 'uid';
//	    	$autobid   = $CI->tender_auto_seting->get($uid);
//	    	$rank_time = !empty($autobid['status']) ? $autobid['rank_time'] : time() * 10000;
//		}
//		
//		//初始化变量
//    	$fundBefore = $balance = $rank = $await = $amount = 0;
//    	
//    	//在自己之前的用户[不含本人]
//    	if (empty($rule)) {
//    		$rule = array(
//    			'cols'	 => array('uid', 'status', 'tender_money_min', 'tender_money_max', 'deal_flag', 'period_min', 'period_max'),
//    			'in'	 => array('status' => array(1, 2)),
//    			'scope'  => array('mt' => array('rank_time' => $rank_time))
//    		);
//    	}
//    	//查询自动投标设置表数据
//    	$rs = $CI->tender_auto_seting->getWidgetRows($rule);
//    	//初始化统计各种标字段
//    	if (empty($data['borrow'])) {
//    		$data['borrow'] = array(
//				'one' 	=> 0,
//				'two' 	=> 0,
//				'three' => 0,
//				'pawn'  => 0,
//				'any' 	=> 0,
//			);
//    	}
//    	extract($data['borrow']);
//    	
//		if (!empty($rs)) {
//			//调用缓存
//	    	$CI->load->driver('cache');
//			$userBalance = array();
//			
//			$cacheName = 'userAccountCache' . $i . 'uid' . $uid;
//			
//			$rows = array();
//			foreach ($rs as $value) {
//				$rows[$value['uid']] = $value;
//			}
//			//读取所以用户的资金数据
//			$request = $CI->cache->memcached->get($cacheName);
//			if (!empty($request)) {
//				$request = !empty($request) ? unserialize(gzuncompress($request)) : array();
//			}
//			//读取失败或过期重新读取数据库
//			if (empty($request)) {
//				$uids = array_keys($rows);
//				//请求接口获取用户资金数据
//				$where  = array(
//		    		'uid'		=> implode($uids, ','),	//UID字符串
//		    		'limit'		=> count($uids),		//读取条数
//		    		'offset'	=> 0,					//偏移量
//		    		'method'	=> 'POST'				//请求方式
//		    	);
//		    	$opm     = 'Acc';
//		    	$request = $CI->api_fund->getSearch($where, $opm);
//			}
//			
//	    	if ($request['error'] == 1) {
//	    		foreach ($request['data']['result'] as $value) {
//	    			if (isset($rows[$value['uid']]) 
//	    				&& $rows[$value['uid']]['tender_money_min'] <= $value['balance']) {
//	    				//统计排名资金
//	    				if ($rows[$value['uid']]['status'] == 1) {
//	    					$fundBefore += $rows[$value['uid']]['tender_money_max'] < $value['balance'] 
//		    					? $rows[$value['uid']]['tender_money_max'] 
//		    					: $value['balance'];
//	    				}
//	    				//剔除用户自己的可用余额
//	    				$value['uid'] == $uid ? $balance = $value['balance'] : '';
//	    				//抵押标 总人数统计
//	    				$rows[$value['uid']]['deal_flag'] == 'mortgage' && $pawn += 1;
//	    				//一月标 总人数统计
//	    				$rows[$value['uid']]['period_min'] == 30 && $one += 1;
//	    				//二月标 总人数统计
//	    				$math = ($rows[$value['uid']]['period_min'] + $rows[$value['uid']]['period_max']) / 60;
//	    				1 < $math && $math < 3 && $two += 1;
//	    				//三月标 总人数统计
//	    				$rows[$value['uid']]['period_max'] == 90 && $three += 1;
//	    				//不限 总人数统计
//	    				$rows[$value['uid']]['period_max'] > 90 && $any += 1;
//	    			}
//	    			//用户资金数据
//	    			if ($value['uid'] == $uid) {
//	    				//用户可用资金
//	    				$balance = numberFormat($value['balance']);
//	    				//用户总待收资金
//	    				$await	 = numberFormat($value['await']);
//	    				//用户总资金
//	    				$amount	 = numberFormat($value['amount']);
//	    			}
//					//统计排名
//					$rank += 1;
//					//缓存用户可用资金数据
//					$userBalance[$value['uid']] = $value['balance'];
//	    		}
//	    		//缓存所以用户的资金数据
//	    		$CI->cache->memcached->save($cacheName, gzcompress(serialize($request), 9), 10);
//	    	}
//		}
//		//在自己之后的用户[含本人]
//		$rule = array(
//			'cols'	 => array('uid', 'status', 'tender_money_min', 'tender_money_max', 'deal_flag', 'period_min', 'period_max'),
//			'in'	 => array('status' => array(1, 2)),
//			'scope'  => array('ltt' => array('rank_time' => $rank_time))
//		);
//		//总排队人数
//		$rankSum = $rank + (isset($data['rank']) ? $data['rank'] : 0) - 1;
//		
//		$data = array(
//			'amount'		=> !$i ? 0 : $amount,										//用户总资金
//			'balance' 		=> !$i ? 0 : $balance,										//用户可用资金
//			'await' 		=> !$i ? 0 : $await,										//用户总待收资金
//			'rank' 			=> !$i ? $rank + 1 : $data['rank'],							//用户开启自动投标排名
//			'rankSum' 		=> !$i ? $rank : ($rankSum > 0 ? $rankSum : 1),				//所有开启自动投标用户排队总人数
//			'fundBefore' 	=> !$i ? $fundBefore : $data['fundBefore'],					//排在该用户之前的排队资金
//			'fundAmount' 	=> !$i ? $fundBefore : $fundBefore + $data['fundBefore'],	//排队总资金
//			'borrow'		=> array(
//				'one' 	 => $one,		//正在排队用户设置的投一月标数量
//				'two' 	 => $two,		//正在排队用户设置的投二月标数量
//				'three'  => $three,		//正在排队用户设置的投三月标数量
//				'pawn' 	 => $pawn,		//正在排队用户设置的投抵押标数量
//				'any' 	 => $any		//正在排队用户设置的投不限标数量
//			),
//			'expire'		=> !$i ? time() : $data['expire'],		//读取数据起始时间戳
//			'autobid'		=> !$i ? $autobid : $data['autobid'],	//读取用户设置的自动投标数据
//		);
//		
//		//对新近用户或关闭自动投标者，并且没设置自动投标的用户 排名就行修正
//		if ($i) {
//			if ((empty($data['autobid']) || (!empty($data['autobid']) && empty($data['autobid']['status'])))) {
//				//请求接口获取用户资金数据 如果用户没设置自动投标 输出默认数据
//				$where  = array('uid' => $uid);
//	            $opm    = 'Acc';
//	            $request= $CI->api_fund->getRow($where, $opm);
//				
//				$data['amount'] 	= isset($request['data']['amount']) ? $request['data']['amount'] : 0;
//				$data['balance'] 	= isset($request['data']['balance']) ? $request['data']['balance'] : 0;
//				$data['await'] 		= isset($request['data']['await']) ? $request['data']['await'] : 0;
//				$data['rank'] 		= 0;
//				$data['fundBefore'] = $data['fundAmount'];
//			}
//			//存入session 用于全局调用
//			$_SESSION['complex'] = $data;
//		}
//		
//		$i++;
//    	
//    	return $i > 1 ? $data : userComplexData($uid, $expire, $rank_time, $rule, $data, $i);
	}
}

/**
 * 发标预告
 * 
 * @author LEE
 * @copyright 20150830
 * @return array
 */
if ( ! function_exists('getTrailer'))
{
	function getTrailer()
	{
		$CI = & get_instance();
		$CI->load->model('borrow_model', 'borrow');
		
		//今日预计发标总额
    	if (isset($_SESSION['MY_SYSTEM']) && isset($_SESSION['MY_SYSTEM']['ISSUING'])
	    	&& (time() - $_SESSION['MY_SYSTEM']['ISSUING']['time']) < 10) {
    		$issuing = $_SESSION['MY_SYSTEM']['ISSUING']['value'];
    	} else {
	    	$where = array(
	    		'sum'  	  => 'amount',
	    		'status'  => 'published',
	    		'is_auto' => 1,
	    		'scope'   => array(
	    			'ltt' => array('auto_time' => strtotime(date('Ymd'))),
	    			'mtt' => array('auto_time' => strtotime(date('Ymd')) + 86399)
				)
	    	);
	    	$issuing = $CI->borrow->getWidgetRows($where);
	    	$issuing = !empty($issuing[0]) && !empty($issuing[0]['amount']) ? $issuing[0]['amount'] : 0;
	    	//存入session
	    	$_SESSION['MY_SYSTEM']['ISSUING'] = array('value' => $issuing, 'time' => time());
    	}
    	
    	return numberFormat($issuing);
	}
}

/**
 * 今日收益、即将还本
 *
 * @param  integer 	$uid		//用户UID [必填]
 * @author LEE
 * @copyright 20150830
 * @return array
 */
if ( ! function_exists('getUserData'))
{
	function getUserData($uid)
	{
		$CI = & get_instance();
		$CI->load->model('user_account_count_model', 'user_account_count');
		$CI->load->model('repay_log_model'	 	   , 'repay_log');
		if (empty($uid)) {
			return false;
		}
		
		$userAcc = array();
		
		//用户统计表
    	$uacount = $CI->user_account_count->get($uid);
    	$params  = array(
//    		'income_count' 		,	//总收益
    		'income_today_count',	//今日收益
//    		'income_week_count' ,	//近一周收益
//    		'income_month_count',	//近一月收益
    	);
    	foreach ($params as $value) {
			if (isset($uacount[$value])) {
				$userAcc[$value] = $uacount[$value];
			} else {
    			$userAcc[$value] = 0;
    		}
    	}
		
		//即将还本 [取明日还本金额]
    	$where = array(
    		'sum'	 	=> 'recover_capital',
    		'uid' 		=> $uid,
    		'status' 	=> 'wait',
    		'scope'		=> array(
    			'ltt' => array('recover_time' => strtotime(date('Y-m-d') + 86400)),
    			'mt'  => array('recover_time' => strtotime(date('Y-m-d') + 86400 * 2))
    		)
    	);
    	$watiCapital = $CI->repay_log->getWidgetRow($where);
    	$userAcc['recover_capital'] = !empty($watiCapital['recover_capital']) ? $watiCapital['recover_capital'] : 0;
    	
    	//数字格式
    	foreach ($userAcc as &$value) {
    		$value = numberFormat($value);
    	}
    	unset($value);
    	
    	return array(
    		'income_today_count' => $userAcc['income_today_count'],	//今日收益
    		'recover_capital'	 => $userAcc['recover_capital'],	//即将还本
    	);
	}
}

/**
 * 自动加tag链接 临时使用
 *
 * @param  array  $article	//文章数据
 * @param  array  $_TAGS	//标签下对应的文章数据
 * @access private
 * @author LEE
 * @copyright 2015-09-18 05:30
 * @return string
 */
if ( ! function_exists('replaceHTML')) 
{
    function replaceHTML(&$article, $_TAGS = array())
    {
    	$CI = & get_instance();
    	
    	$CI->load->model('tags_model', 'article_tags');
    	$_COMMON = $CI->config->item('common');
    	
    	$content = $article['content'];
    	$tags = is_array($article['tags']) ? $article['tags'] : explode(',', $article['tags']);
    	
    	//首先对p2p、p2p网贷、p2p理财进行替换 只替换第一个
		$content = preg_replace("/([^>])(p2p网贷)/i", "\\1<a href='{$_COMMON['hostComplete']}' target='_blank'>\\2</a>", $content, 1);
		$content = preg_replace("/([^>])(p2p理财)/i", "\\1<a href='{$_COMMON['hostComplete']}' target='_blank'>\\2</a>", $content, 1);
		$content = preg_replace("/([^>])(P2P平台)/i", "\\1<a href='{$_COMMON['hostComplete']}' target='_blank'>\\2</a>", $content, 1);
		$content = preg_replace("/([^>])(p2p)/i", "\\1<a href='{$_COMMON['hostComplete']}' target='_blank'>\\2</a>", $content, 1);
    	
		foreach ($tags as $value) {
			if (in_array($value, array('p2p网贷', 'p2p', 'p2p理财', 'p2p平台', 'P2P网贷', 'P2P', 'P2P理财', 'P2P平台'))) {
				continue;
			}
			
			if (isset($article['tagsInfo']) && isset($article['tagsInfo'][$value])) {
				$articleTag = $article['tagsInfo'][$value];
			} else {
				$rule = array(
					'eq' => array('keyword' => $value)
				);
				$articleTag = $CI->article_tags->getWidgetRow($rule);
			}
			
			if (empty($articleTag)) {
				continue;
			}
			
			$lotter = strtolower($articleTag['alpha']);
			
			if (preg_match("/[>]{$value}|{$value}[<]/", $content)) {
				continue;
			} elseif (preg_match("/{$value}/i", $content)) {
				$style = isset($_TAGS[$articleTag['id']]) ? ' cur' : '';
				$content = preg_replace("/({$value})/i", "<a target=\"_blank\" data-id=\"key{$articleTag['id']}\" class=\"keyword{$style}\" href=\"{$_COMMON['hostComplete']}tag/{$lotter}-{$articleTag['id']}.html\">\\1</a>", $content, 1);
			}
        }
        
        $article['content'] = $content;
    }
}

/**
 * 取得memcache数据
 * @author zhoushuai
 * @param string $key
 * @return \array:
 */
if (!function_exists('getMmemData'))
{
    function getMmemData($key){
        $CI = & get_instance();
        $CI->load->driver('cache');
        $return = array();
        if($CI->cache->memcached->is_supported() === true){
            $key = md5($key);
            $cache = $CI->cache->memcached->get($key);
            return $cache;
        }
        return $return;
    }
}

/**
 * 保存memcache数据
 * @author zhoushuai
 * @param string $key
 * @return boolean
 */
if (!function_exists('setMmemData'))
{
    function setMmemData($key, $data, $replace = false,$ttl = 60){
        $CI = & get_instance();
        $CI->load->driver('cache');
        if ($CI->cache->memcached->is_supported() === true) {
            $key = md5($key);
            if (!$replace) {
                $CI->cache->memcached->save($key, $data, $ttl);
            } else {
                $CI->cache->memcached->replace($key, $data, $ttl);
            }
            return true;
        }
        return false;
    }
}


/**
 * 删除memcache数据
 * @author zhoushuai
 * @param string $key
 * @return boolean
 */
if (!function_exists('delMmemData'))
{
    function delMmemData($key){
        $CI = & get_instance();
        $CI->load->driver('cache');
        if ($CI->cache->memcached->is_supported() === true) {
            $key = md5($key);
            $CI->cache->memcached->delete($key);
            return true;
        }
        return false;
    }
}


/**
 * 清空memcache数据
 * @author zhoushuai
 * @param string $key
 * @return boolean
 */
if (!function_exists('cleanMmemData'))
{
    function cleanMmemData(){
        $CI = & get_instance();
        $CI->load->driver('cache');
        if ($CI->cache->memcached->is_supported() === true) {
            $CI->cache->memcached->clean();
        }
        return false;
    }
}







