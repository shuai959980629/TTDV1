<?php
/**
* 支付主要调用类
*/
require_once ("lib/llpay_submit.class.php");
require_once ("lib/llpay_notify.class.php");
include_once ('lib/llpay_cls_json.php');
require_once ("lib/llpay_core.function.php");

class Pay implements Paysubclass
{
    
    private $CI = NULL;

    private $llpay_config = null;


    public function __construct()
    {
        require_once ("llpay.config.php");
        $this->llpay_config = $llpay_config;
        $this->CI =& get_instance();
        $this->CI->config->load('payment');
        $Payment = $this->CI->config->item('Payment');
        $this->llpay_config['oid_partner'] = $Payment['LLPay']['oid_partner'];
        $this->llpay_config['key'] = $Payment['LLPay']['key'];
    }
    /**
     * 支付提交方法
     * @param array $data 支付相关参数
     */
    public function PaySubmit($data=array())
    {
        /**************************请求参数**************************/

        //商户用户唯一编号
        $user_id = $data['user_id'];

        //支付类型
        $busi_partner = 101001;

        //商户订单号
        $no_order = $data['TransID'];
        //商户网站订单系统中唯一订单号，必填

        //付款金额
        $money_order = $data['OrderMoney'];
        //必填

        //商品名称
        $name_goods = '用户充值';

        //订单地址
        $url_order = '';

        //订单描述
        $info_order = '';

        //银行网银编码
        $bank_code = '';

        //支付方式
        $pay_type = 'D';

        //卡号
        $card_no = '';

        //银行账号姓名
        $acct_name = $data['acct_name'];

        //身份证号
        $id_no = $data['id_no'];

        //协议号
        $no_agree = '';

        //修改标记
        $flag_modify = '';

        //风险控制参数
        $risk_item = $data['risk_item'];

        //分账信息数据
        $shareing_data = '';

        //返回修改信息地址
        $back_url = '';

        //订单有效期
        $valid_order = $this->llpay_config['valid_order'];

        //服务器异步通知页面路径
        $notify_url = $data['ReturnUrl'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数

        //页面跳转同步通知页面路径
        $return_url = $data['PageUrl'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

        /************************************************************/
        date_default_timezone_set('PRC');
        //构造要请求的参数数组，无需改动
        $parameter = array (
            "version" => trim($this->llpay_config['version']),
            "oid_partner" => trim($this->llpay_config['oid_partner']),
            "sign_type" => trim($this->llpay_config['sign_type']),
            "userreq_ip" => trim($this->llpay_config['userreq_ip']),
            "id_type" => trim($this->llpay_config['id_type']),
            "valid_order" => trim($this->llpay_config['valid_order']),
            "user_id" => $user_id,
            "timestamp" => local_date('YmdHis', time()),
            "busi_partner" => $busi_partner,
            "no_order" => $no_order,
            "dt_order" => local_date('YmdHis', $data['dt_order']),
            "name_goods" => $name_goods,
            "info_order" => $info_order,
            "money_order" => $money_order,
            "notify_url" => $notify_url,
            "url_return" => $return_url,
            "url_order" => $url_order,
            "bank_code" => $bank_code,
            "pay_type" => $pay_type,
            "no_agree" => $no_agree,
            "shareing_data" => $shareing_data,
            "risk_item" => $risk_item,
            "id_no" => $id_no,
            "acct_name" => $acct_name,
            "flag_modify" => $flag_modify,
            "card_no" => $card_no,
            "back_url" => $back_url
        );
        //建立请求
        $llpaySubmit = new LLpaySubmit($this->llpay_config);
        $html_text = $llpaySubmit->buildRequestForm($parameter, "post", "确认");
        echo $html_text;
        exit();
     }
    /**
     * 支付返回数据校验
     */
    public function Callback()
    {
        //计算得出通知验证结果
        $llpayNotify = new LLpayNotify($this->llpay_config);
        $verify_notify_result = $llpayNotify->verifyNotify();
        if($verify_notify_result){
            $is_notify = true;
            include_once ('lib/llpay_cls_json.php');
            $json = new JSON;
            $str = file_get_contents("php://input");
            $val = $json->decode($str);
            $no_order = trim($val-> {
                'no_order' });
            $money_order = trim($val-> {
                'money_order' });
            $result_pay = trim($val-> {
                'result_pay' });
            if ($result_pay=='SUCCESS') {
                return array('result'=>true,'amount'=>$money_order,'trans_no'=>$no_order,'type'=>2);
            }else{
                return array('result'=>true,'amount'=>$money_order,'trans_no'=>$no_order,'type'=>2);
            }
        }else{
            return array('result'=>false,'type'=>2);
        }
    }
    // 支付返回数据校验同步
    public function Callback_return()
    {
        $llpayNotify = new LLpayNotify($this->llpay_config);
        $verify_return_result = $llpayNotify->verifyReturn();
        if ($verify_return_result) {
            //商户订单号
            $no_order = $_POST['no_order' ];
            //交易金额
            $money_order = $_POST['money_order' ];
            //支付结果
            $result_pay =  $_POST['result_pay'];

            if($result_pay == 'SUCCESS') {
                return array('result'=>true,'amount'=>$money_order,'trans_no'=>$no_order,'type'=>1);
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（no_order）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
            }else {
               return array('result'=>true,'amount'=>$money_order,'trans_no'=>$no_order,'type'=>1);
            }
        }else{
            return array('result'=>false,'type'=>1);
        }
    }

    private $order_info_url = 'https://yintong.com.cn/traderapi/orderquery.htm';
    public function getOrderInfo($trans_no,$type='normal',$order_time = 0)
    {
        $query = $this->CI->db->get_where('recharge_order', array('order_no'=>$trans_no));
        $recharg=$query->row_array();
        $post_data = array(
            'dt_order' => date('YmdHis',strtotime($recharg['created'])),
            'no_order' => $trans_no,
            'oid_partner' => trim($this->llpay_config['oid_partner'])
            );
        $sign_data = $post_data;
        $sign_data['sign_type'] = strtoupper(trim($this->llpay_config['sign_type']));
        $sign_data['key'] = trim($this->llpay_config['key']);
        $post_data['sign'] = md5(createLinkstring($sign_data));
        $post_data['sign_type'] = strtoupper(trim($this->llpay_config['sign_type']));
        $json_data = json_encode($post_data);
        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_URL, $this->order_info_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data))
        );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $json_data );
        $check_info = curl_exec ( $ch );
        curl_close ( $ch );
        $result_arr = json_decode($check_info,true);
        if ($type == 'normal') {
            //整理数据返回
            $pay_status = 'N';
            if (!isset($result_arr['result_pay'])) {
                $return_array = array(
                    'status' => '0',
                    'succMoney' =>0,
                    'TransID' => $trans_no,
                    'SuccTime' => 0,
                    'CheckResult' => 'N'
                );
            }else{
                switch ($result_arr['result_pay']) {
                    case 'WAITING':
                        $pay_status = 'P';
                        break;
                    case 'PROCESSING':
                        $pay_status = 'P';
                        break;
                    case 'FAILURE':
                        $pay_status = 'F';
                        break;
                    case 'SUCCESS':
                        $pay_status = 'Y';
                        break;
                    case 'REFUND':
                        $pay_status = 'R';
                        break;
                    default:
                        $pay_status ='N';
                        break;
                }
                $return_array = array(
                    'status' => '1',
                    'succMoney' =>$result_arr['money_order'],
                    'TransID' => ($result_arr['no_order']!='')?$result_arr['no_order']:$trans_no,
                    'SuccTime' => $result_arr['dt_order'],
                    'CheckResult' => $pay_status
                );
            }
            return $return_array;
        }else if ($type == 'all') {
            return $result_arr;
        }
    }
}
?>
