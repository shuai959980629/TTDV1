<?php
/**
* 支付主要调用类
*/
require_once ("lib/wap/llpay_submit.class.php");
require_once ("lib/wap/llpay_notify.class.php");
include_once ('lib/wap/llpay_cls_json.php');

class Paywap implements Paysubclass
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
        $this->llpay_config['app_request'] = '3';
    }
    /**
     * 支付提交方法
     * @param array $data 支付相关参数
     */
    public function PaySubmit($data=array())
    {
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

        //订单描述
        $info_order = '';

        //卡号
        $card_no = isset($data['card_no'])?$data['card_no']:'';

        //姓名
        $acct_name = $data['acct_name'];

        //身份证号
        $id_no = $data['id_no'];

        //协议号
        $no_agree = '';

        //风险控制参数
        $risk_item = $data['risk_item'];

        //订单有效期
        $valid_order = $this->llpay_config['valid_order'];

        //服务器异步通知页面路径
        $notify_url = $data['ReturnUrl'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数

        //页面跳转同步通知页面路径
        $return_url = $data['PageUrl'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array (
            "oid_partner" => trim($this->llpay_config['oid_partner']),
            "app_request" => trim($this->llpay_config['app_request']),
            "sign_type" => trim($this->llpay_config['sign_type']),
            "valid_order" => trim($this->llpay_config['valid_order']),
            "user_id" => $user_id,
            "busi_partner" => $busi_partner,
            "no_order" => $no_order,
            "dt_order" => local_date('YmdHis', $data['dt_order']),
            "name_goods" => $name_goods,
            "info_order" => $info_order,
            "money_order" => $money_order,
            "notify_url" => $notify_url,
            "url_return" => $return_url,
            "card_no" => $card_no,
            "acct_name" => $acct_name,
            "id_no" => $id_no,
            "no_agree" => $no_agree,
            "risk_item" => $risk_item,
            "valid_order" => $valid_order
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
        $llpayNotify = new LLpayNotify($this->llpay_config);
        $llpayNotify->verifyNotify();
        if ($llpayNotify->result) { //验证成功
            //获取连连支付的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $no_order = $llpayNotify->notifyResp['no_order'];//商户订单号
            $result_pay = $llpayNotify->notifyResp['result_pay'];//支付结果，SUCCESS：为支付成功
            $money_order = $llpayNotify->notifyResp['money_order'];// 支付金额
            if($result_pay == "SUCCESS"){
                return array('result'=>true,'amount'=>$money_order,'trans_no'=>$no_order,'type'=>2);
            }else{
                return array('result'=>true,'amount'=>0,'trans_no'=>$no_order,'type'=>2);
            }
        } else {
            return array('result'=>false,'type'=>2);
        }
    }
    //同步返回数据校验
    public function Callback_return()
    {
        //计算得出通知验证结果
        $llpayNotify = new LLpayNotify($this->llpay_config);
        $verify_result = $llpayNotify->verifyReturn();
        if($verify_result) {
            $json = new JSON;
            $res_data = $_POST["res_data"];
            log_message('error',json_encode(array('data'=>$res_data)),'llpay_log');

            //商户编号
            $money_order = $json->decode($res_data)-> {'money_order' };

            //商户订单号
            $no_order = $json->decode($res_data)-> {'no_order' };

            //支付结果
            $result_pay =  $json->decode($res_data)-> {'result_pay' };

            log_message('error',json_encode(array('result'=>true,'amount'=>$money_order,'trans_no'=>$no_order,'type'=>1)),'llpay_log');
            if($result_pay == 'SUCCESS') {
                return array('result'=>true,'amount'=>$money_order,'trans_no'=>$no_order,'type'=>1);
            }else {
               return array('result'=>true,'amount'=>0,'trans_no'=>$no_order,'type'=>1);
            }
        }else{
            return array('result'=>false,'type'=>1);
        }
    }
}
?>
