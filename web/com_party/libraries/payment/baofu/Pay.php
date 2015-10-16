<?php
/**
* 支付主要调用类
*/
class Pay implements Paysubclass
{
    private $CI = NULL;

    //md5密钥（KEY）
    private $Md5key = '';

    /**
     * 交易查询地址
     */
    private $orderInfo_url = 'https://gw.baofoo.com/order/query';

    /**
     * 商户ID
     */
    private $MemberID = '';

    /**
     * 终端ID
     */
    private $TerminalID = '';

    /**
     * @MD5签名
     */
    private $Md5Sign = '';


    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('payment');
        $Payment = $this->CI->config->item('Payment');
        $this->Md5key = $Payment['Baofu']['Md5key'];
        $this->MemberID = $Payment['Baofu']['MemberID'];
        $this->TerminalID = $Payment['Baofu']['TerminalID'];
    }
    /**
     * 支付提交方法
     * @param array $data 支付相关参数
     */
    public function PaySubmit($data=array())
    {
        if ($this->MemberID=='') {
            echo "no MemberID";
            exit();
        }
        $data['MemberID']=$this->MemberID;
        if (!isset($data['TransID'])) {//流水号
            echo "no TransID";
            exit();
        }
        $data['TradeDate']=date('YmdHis',time());
        if (!isset($data['OrderMoney'])) {//订单金额
            echo "no OrderMoney";
            exit();
        }
        $data['OrderMoney']*=100;
        if (!isset($data['PageUrl'])) {//通知商户页面端地址
            echo "no PageUrl";
            exit();
        }
        if (!isset($data['ReturnUrl'])) {//服务器底层通知地址
            echo "no ReturnUrl";
            exit();
        }
        $data['NoticeType']=isset($data['NoticeType'])?$data['NoticeType']:0;
        $data['PayID']=isset($data['PayID'])?$data['PayID']:'';
        $MARK = "|";
        //MD5签名格式
        $data['Signature']=md5($data['MemberID'].$MARK.$data['PayID'].$MARK.$data['TradeDate'].$MARK.$data['TransID'].$MARK.$data['OrderMoney'].$MARK.$data['PageUrl'].$MARK.$data['ReturnUrl'].$MARK.$data['NoticeType'].$MARK.$this->Md5key);
        $data['TerminalID'] = $this->TerminalID;
        return $this->getUrl($data);
    }
    private function getUrl($data=array())
    {
        extract($data);
        $url  ="https://gw.baofoo.com/payindex?";
        $url .= "MemberID={$MemberID}";//商户号
        $url .= "&PayID={$PayID}";//支付方式
        $url .= "&TradeDate={$TradeDate}";//交易时间
        $url .= "&TransID={$TransID}";//流水号
        $url .= "&OrderMoney={$OrderMoney}";//订单金额
        $url .= "&PageUrl={$PageUrl}";//通知商户页面端地址
        $url .= "&ReturnUrl={$ReturnUrl}";//服务器底层通知地址
        $url .= "&NoticeType={$NoticeType}";//通知类型
        $url .= "&Signature={$Signature}";
        $url .= "&TerminalID={$TerminalID}";
        $url .= "&ProductName=";//产品名称
        $url .= "&Amount=";//商品数量
        $url .= "&Username=";//支付用户名
        $url .= "&AdditionalInfo=";//订单附加消息
        $url .= "&InterfaceVersion=4.0";
        $url .= "&KeyType=1";
        return $url;
    }
    /**
     * 支付返回数据校验
     */
    public function Callback()
    {

        $MemberID   = $_REQUEST['MemberID'];
        $TerminalID = $_REQUEST['TerminalID'];
        $TransID    = $_REQUEST['TransID'];
        $Result     = $_REQUEST['Result'];
        $ResultDesc = $_REQUEST['ResultDesc'];
        $FactMoney      = $_REQUEST['FactMoney'];
        $AdditionalInfo = $_REQUEST['AdditionalInfo'];
        $SuccTime       = $_REQUEST['SuccTime'];
        $Md5Sign    = $_REQUEST['Md5Sign'];
        $MARK       = "~|~";

        $SignString = 'MemberID='.$MemberID.$MARK.'TerminalID='.$TerminalID.$MARK.'TransID='.$TransID.$MARK.'Result='.$Result.$MARK.'ResultDesc='.$ResultDesc.$MARK.'FactMoney='.$FactMoney.$MARK.'AdditionalInfo='.$AdditionalInfo.$MARK.'SuccTime='.$SuccTime.$MARK.'Md5Sign='.$this->Md5key;
        $WaitSign = md5($SignString);


        // error_log(var_export($_REQUEST, TRUE), 3, ROOT_PATH.'/data/errors.log');
        // error_log($WaitSign, 3 , ROOT_PATH.'/data/errors.log');
        // error_log($SignString, 3 , ROOT_PATH.'/data/errors.log');

        if ($Md5Sign == $WaitSign)
        {
            if ($Result == 1) {
                return array('result'=>true,'amount'=>$_REQUEST['factMoney'],'trans_no'=>$_REQUEST['TransID']);
            }
            return array('result'=>false);
        }else{
            return array('result'=>false);
        }
    }

    private function getField($TransID)
    {
        return "MemberID={$this->MemberID}&TerminalID={$this->TerminalID}&TransID={$TransID}&Md5Sign={$this->Md5Sign}";
    }

    private function getMD5SIGN($TransID)
    {
        $this->Md5Sign = md5($this->MemberID.'|'.$this->TerminalID.'|'.$TransID.'|'.$this->Md5key);
    }

    private function ckMD5SIGN($data){
        $Md5Sign= md5($data['MemberID'].'|'.$data['TerminalID'].'|'.$data['TransID'].'|' .$data['CheckResult'].'|'.$data['succMoney'].'|'.$data['SuccTime'].'|'.$this->Md5key);
        if($Md5Sign===$data['Md5Sign']){
            return TRUE;
        }
        return false;
    }
    /**
     * 获取订单信息
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function getOrderInfo($trans_no,$type='normal')
    {
        $this->getMD5SIGN($trans_no);
        $data = $this->getField($trans_no);
        $result = https_request($this->orderInfo_url,$data);
        if (empty($result)) {
            return array('status'=>'0');
        }
        $ckResult = explode('|',$result);
        $oData = array(
            'MemberID'=>$ckResult[0],//商户ID
            'TerminalID'=>$ckResult[1],//终端ID
            'TransID'=>$ckResult[2],//订单ID
            'CheckResult'=>$ckResult[3], //支付结果 Y：成功F：失败P：处理中N：没有订单
            'succMoney'=>$ckResult[4],//实际成功金额
            'SuccTime'=>$ckResult[5],//支付完成时间
            'Md5Sign'=>$ckResult[6] //交易签名 MD5({MemberID}|{TerminalID}|{TransID}|{CheckResult}|{succMoney}|{SuccTime}|{Md5Sign})
        );
        $ck = $this->ckMD5SIGN($oData);
        if($ck){
            if ($type=='normal') {
                return array(
                    'status' => '1',
                    'TransID'=>$oData['TransID'],
                    'succMoney'  =>$oData['succMoney'] / 100,
                    'SuccTime'  =>date('Y-m-d H:i:s',strtotime($oData['SuccTime'])),
                    'CheckResult' =>$oData['CheckResult']
                );   
            }elseif ($type=='all') {
                return $oData;
            }
        }else{
            return array('status'=>'0');
        }
    }
}
?>
