<?php
/**
* 支付主要调用类
*/
include 'yeepayCommon.php';

class Pay implements Paysubclass
{
    private $CI = NULL;

    private $MemberID = null;

    private $merchantKey = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('payment');
        $Payment = $this->CI->config->item('Payment');
        $this->MemberID = $Payment['Yibao']['MemberID'];
        $this->merchantKey = $Payment['Yibao']['merchantKey'];
    }
    //支付地址
    private $reqURL_onLine = "https://www.yeepay.com/app-merchant-proxy/node";
    //订单查询地址
    private $reqURL_Info = "https://cha.yeepay.com/app-merchant-proxy/node";
    /**
     * 支付提交方法
     * @param array $data 支付相关参数
     */
    public function PaySubmit($data=array())
    {
        #   商家设置用户购买商品的支付信息.
        ##易宝支付平台统一使用GBK/GB2312编码方式,参数如用到中文，请注意转码
        
        $post_data['p1_MerId']                   = $this->MemberID;

        $post_data['p0_Cmd']                     = "Buy";

        #   商户订单号,选填.
        ##若不为""，提交的订单号必须在自身账户交易中唯一;为""时，易宝支付会自动生成随机的商户订单号.
        $post_data['p2_Order']                   = $data['TransID'];

        #   支付金额,必填.
        ##单位:元，精确到分.
        $post_data['p3_Amt']                     = $data['OrderMoney'];

        #   交易币种,固定值"CNY".
        $post_data['p4_Cur']                     = "CNY";

        #   商品名称
        ##用于支付时显示在易宝支付网关左侧的订单产品信息.
        $post_data['p5_Pid']                     = '';

        #   商品种类
        $post_data['p6_Pcat']                    = '';

        #   商品描述
        $post_data['p7_Pdesc']                   = '';

        #   商户接收支付成功数据的地址,支付成功后易宝支付会向该地址发送两次成功通知.
        $post_data['p8_Url']                     = isset($data['ReturnUrl'])?$data['ReturnUrl']:$data['PageUrl'];  

        $post_data['p9_SAF']                     = '0';

        #   商户扩展信息
        ##商户可以任意填写1K 的字符串,支付成功时将原样返回.                                               
        $post_data['pa_MP']                      = '';

        #   支付通道编码
        ##默认为""，到易宝支付网关.若不需显示易宝支付的页面，直接跳转到各银行、神州行支付、骏网一卡通等支付页面，该字段可依照附录:银行列表设置参数值.          
        $post_data['pd_FrpId']                   = '';

        #   应答机制
        ##默认为"1": 需要应答机制;
        $post_data['pr_NeedResponse']    = "0";
        #调用签名函数生成签名串
        $post_data['hmac'] = $this->getReqHmacString($post_data['p2_Order'],$post_data['p3_Amt'],$post_data['p4_Cur'],$post_data['p5_Pid'],$post_data['p6_Pcat'],$post_data['p7_Pdesc'],$post_data['p8_Url'],$post_data['pa_MP'],$post_data['pd_FrpId'],$post_data['pr_NeedResponse']);
        
        //构造form表单提交 
        $this->SubmitForm($post_data,$this->reqURL_onLine);
        exit();
     }
    /**
     * 支付返回数据校验
     */
    public function Callback()
    {
        $this->CI->config->load('payment');
        $Payment = $this->CI->config->item('Payment');

        #   只有支付成功时易宝支付才会通知商户.
        ##支付成功回调有两次，都会通知到在线支付请求参数中的p8_Url上：浏览器重定向;服务器点对点通讯.

        #   解析返回参数.
        // $return = getCallBackValue($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);

        $r0_Cmd     = $this->CI->input->get_post('r0_Cmd');
        $r1_Code    = $this->CI->input->get_post('r1_Code');
        $r2_TrxId   = $this->CI->input->get_post('r2_TrxId');
        $r3_Amt     = $this->CI->input->get_post('r3_Amt');
        $r4_Cur     = $this->CI->input->get_post('r4_Cur');
        $r5_Pid     = $this->CI->input->get_post('r5_Pid');
        $r6_Order   = $this->CI->input->get_post('r6_Order');
        $r7_Uid     = $this->CI->input->get_post('r7_Uid');
        $r8_MP      = $this->CI->input->get_post('r8_MP');
        $r9_BType   = $this->CI->input->get_post('r9_BType'); 
        $hmac           = $this->CI->input->get_post('hmac');

        #   判断返回签名是否正确（True/False）
        $bRet = $this->CheckHmac($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);
        #   以上代码和变量不需要修改.
        
        #   校验码正确.
        if($bRet){
            if($r1_Code=="1"){
                return array('result'=>true,'amount'=>$r3_Amt,'trans_no'=>$r6_Order,'type'=>$r9_BType);
            }else{
                return array('result'=>false,'type'=>$r9_BType);
            } 
        }else{
            // echo "交易信息被篡改";
            return array('result'=>false,'type'=>$r9_BType);
        }
    }

    public function getOrderInfo($trans_no,$type='normal')
    {
        require_once 'HttpClient.class.php';

        //业务类型
        $post_data['p0_Cmd'] = 'QueryOrdDetail';

        //商户编号
        $post_data['p1_MerId'] = $this->MemberID;

        //商户订单号
        $post_data['p2_Order'] = $trans_no;

        //获取签名
        $post_data['hmac'] = $this->getHmacString($post_data,$this->merchantKey);

        $pageContents = HttpClient::quickPost($this->reqURL_Info, $post_data);

        $result = explode("\n",$pageContents);
        $result_arr = array();
        foreach ($result as $key => $value) {
            if ($value != '') {
                $tmp = explode('=',$value);
                if (!empty($tmp)) {
                    $result_arr[$tmp[0]] = $tmp[1];
                }
            }
        }
        if ($type == 'normal') {
            //整理数据返回
            $pay_status = 'N';
            if ($result_arr['r1_Code']==50) {
                $pay_status = 'N';
            }
            switch ($result_arr['rb_PayStatus']) {
                case 'INIT':
                    $pay_status = 'P';
                    break;
                case 'CANCELED':
                    $pay_status = 'F';
                    break;
                case 'SUCCESS':
                    $pay_status = 'Y';
                    break;
                default:
                    $pay_status ='N';
                    break;
            }
            $return_array = array(
                'status' => '1',
                'succMoney' =>$result_arr['r3_Amt'],
                'TransID' => ($result_arr['r6_Order']!='')?$result_arr['r6_Order']:$trans_no,
                'SuccTime' => $result_arr['ry_FinshTime'],
                'CheckResult' => $pay_status
                );
            return $return_array;
        }else if ($type == 'all') {
            return $result_arr;
        }
    }
    //构造表单提交函数
    private function SubmitForm($post_data , $url){
        $echostr = "<body onLoad='document.yeepay.submit();'><form name='yeepay' action='$url' method='post'>";
        foreach ($post_data as $key => $value) {
            $echostr .= "<input type='hidden' name='$key'                 value='$value'>";
        }
        $echostr .= "</form></body>";
        echo $echostr;
    }
    private function CheckHmac($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac)
    {
        if($hmac==$this->getCallbackHmacString($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType))
            return true;
        else
            return false;
    }
            
      
    private function HmacMd5($data,$key)
    {
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // Hacked by Lance Rushing(NOTE: Hacked means written)

        //需要配置环境支持iconv，否则中文参数不能正常处理
        $key = iconv("GBK","UTF-8",$key);
        $data = iconv("GBK","UTF-8",$data);

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
        $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*",md5($k_ipad . $data)));
    }

    private function getCallbackHmacString($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType)
    {  
        #取得加密前的字符串
        $sbOld = "";
        #加入商家ID
        $sbOld = $sbOld.$this->MemberID;
        #加入消息类型
        $sbOld = $sbOld.$r0_Cmd;
        #加入业务返回码
        $sbOld = $sbOld.$r1_Code;
        #加入交易ID
        $sbOld = $sbOld.$r2_TrxId;
        #加入交易金额
        $sbOld = $sbOld.$r3_Amt;
        #加入货币单位
        $sbOld = $sbOld.$r4_Cur;
        #加入产品Id
        $sbOld = $sbOld.$r5_Pid;
        #加入订单ID
        $sbOld = $sbOld.$r6_Order;
        #加入用户ID
        $sbOld = $sbOld.$r7_Uid;
        #加入商家扩展信息
        $sbOld = $sbOld.$r8_MP;
        #加入交易结果返回类型
        $sbOld = $sbOld.$r9_BType;

        
        return $this->HmacMd5($sbOld,$this->merchantKey);

    }
    #签名函数生成签名串
    private function getReqHmacString($p2_Order,$p3_Amt,$p4_Cur,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pd_FrpId,$pr_NeedResponse)
    {
        # 业务类型
        # 支付请求，固定值"Buy" .   
        $p0_Cmd = "Buy";
            
        #   送货地址
        # 为"1": 需要用户将送货地址留在易宝支付系统;为"0": 不需要，默认为 "0".
        $p9_SAF = "0";
            
        #进行签名处理，一定按照文档中标明的签名顺序进行
        $sbOld = "";
        #加入业务类型
        $sbOld = $sbOld.$p0_Cmd;
        #加入商户编号
        $sbOld = $sbOld.$this->MemberID;
        #加入商户订单号
        $sbOld = $sbOld.$p2_Order;     
        #加入支付金额
        $sbOld = $sbOld.$p3_Amt;
        #加入交易币种
        $sbOld = $sbOld.$p4_Cur;
        #加入商品名称
        $sbOld = $sbOld.$p5_Pid;
        #加入商品分类
        $sbOld = $sbOld.$p6_Pcat;
        #加入商品描述
        $sbOld = $sbOld.$p7_Pdesc;
        #加入商户接收支付成功数据的地址
        $sbOld = $sbOld.$p8_Url;
        #加入送货地址标识
        $sbOld = $sbOld.$p9_SAF;
        #加入商户扩展信息
        $sbOld = $sbOld.$pa_MP;
        #加入支付通道编码
        $sbOld = $sbOld.$pd_FrpId;
        #加入是否需要应答机制
        $sbOld = $sbOld.$pr_NeedResponse;

        return $this->HmacMd5($sbOld,$this->merchantKey);
      
    } 
    /**
     * 根据数组序列生成签名
     */
    private function getHmacString($data,$merchantKey){
        $sbOld = '';
        if (is_array($data)) {
            $oldstr ='';
            foreach ($data as $key => $value) {
                $oldstr .= $value;
            }
            $sbOld = $this->HmacMd5($oldstr,$merchantKey);
        }
        return $sbOld;
    }
}
?>
