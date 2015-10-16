<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @微信模块
 * @author zhoushuai
 * @copyright(c) 2015-08-03
 * @version
 */
class WeChat
{
    private $log; //日志文件内容模型
    private $CI = null;
    private $debug =  true;//是否debug的状态标示，方便我们在调试的时候记录一些中间数据 //日志开关。可填值：true、
    public  $msgtype = 'text';   //('text','image','location')
    public  $msg = array();
    public  $openid;

    public function __construct()
    {
        if (ENVIRONMENT === 'production'){
            $this->debug = false;
        }
    }


    /**
     * @author zhoushuai
     * @微信验证
     */
    public function valid()
    {
        if ($_SERVER['REQUEST_METHOD']=='GET' && $this->checkSignature()) {
            echo $_GET['echostr'];
            exit;
        }else{
            $this->debuglog('认证失败');
            echo '';
            exit;
        }
    }

    /**
     * @微信验证
     */
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = array(TOKEN, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }



    /**
     * @初始化
     */
    private function __init(){
        $this->CI = &get_instance();
        $this->initWeChat();
    }


    /**
     * @author zhoushuai
     * @初始化用户发过来的消息（消息内容和消息类型）
     */
    private function initWeChat()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if(!empty($postStr)){
            if ($this->debug) {
                $this->debuglog($postStr);
            }
            if (!empty($postStr)) {
                $this->msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $this->msgtype = strtolower($this->msg['MsgType']);
                $this->openid = $this->msg['FromUserName']; //发送方帐号（一个OpenID）
            }
        }else{
            echo '';
            exit;
        }
    }


    /**
     * @回复
     */
    private function reply($data)
    {
        if ($this->debug) {
            $this->debuglog($data);
        }
        echo $data;
        exit;
    }

    /**
     * @响应用户微信请求
     */
    public function response(){
        $this->__init();
        $reply = '';
        switch($this->msgtype){
            case 'event':
                $this->CI->load->model('Wxevent_model','wxevent');
                $reply = $this->CI->wxevent->receiveEvent($this);
                break;
            case 'text':
                $this->CI->load->model('Autoaply_model','aply');
                $reply = $this->CI->aply->autoAply($this);
                break;
            case 'location':

                break;
            default:
                $content = "您好，欢迎您关注图腾贷！";
                $reply = $this->makeText($content);
                break;
        }
        $this->reply($reply);
    }




    /**
     * @回复文本消息
     */
    public function makeText($text='')
    {
        $CreateTime = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        return sprintf($textTpl,$text);
    }



    /**
     * @根据数组参数回复图文消息
     */
    public function makeNews($newsData=array())
    {
        $CreateTime = time();
        $itemsCount = count($newsData['items']);
        $itemsCount = $itemsCount < 10 ? $itemsCount : 10;//微信公众平台图文回复的消息一次最多10条
        $header = "<xml>
            <ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>{$itemsCount}</ArticleCount><Articles>";
        $newTplItem = "<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>";
        $footer = "</Articles></xml>";
        $Content = '';
        if ($itemsCount) {
            foreach ($newsData['items'] as $key => $item) {
                if ($key<=9) {
                    $Content .= sprintf($newTplItem,$item['title'],$item['description'],$item['picurl'],$item['url']);
                }
            }
        }
        return $header . $Content . $footer;
    }



    /**
     * 将消息转发到多客服
     */
    public function moreTransferCustomerService()
    {
        $CreateTime = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[transfer_customer_service]]></MsgType>
            </xml>";
        return $textTpl;
    }


    /**
     * 消息转发到指定客服
     */
    public function oneTransferCustomerService($KfAccount)
    {
        $CreateTime = time();
        $textTpl = "<xml>
            <ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[transfer_customer_service]]></MsgType>
            <TransInfo>
                <KfAccount>%s</KfAccount>
            </TransInfo>
            </xml>";
        return sprintf($textTpl,$KfAccount);
    }

    /**
     * 打印日志
     * @param log 日志内容
     */
    public function debuglog($data)
    {
        $this->log = '
#===============================================================================================================
#DEBUG-WeChat start |执行时间：%s (ms)
#===============================================================================================================
#请求接口:%s
#User-Agent:%s
#返回数据(Array):
$data=%s;
#==================================================debug end====================================================' .
            "\r\n\r\n";
        debug_log($this->log, $data);
    }







}
