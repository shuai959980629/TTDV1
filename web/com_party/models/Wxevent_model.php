<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @事件推送
 * @微信公众平台开发关注及取消关注等事件业务逻辑
 * @author zhoushuai
 * @license		图腾贷 [手机端]
 * @category 2015-08-25
 * @version
 */
class Wxevent_model extends CI_model
{
    private $wx; //微信对象
    private $openid;
    private $event; //事件
    private $reply = '';
    private $content='';


    public function __construct()
    {
        parent::__construct();

    }

    private function _init($wx){
        $this->wx = $wx;
        $this->openid = $this->wx->openid;
        $this->event = $this->wx->msg['Event'];
    }

    /**
     * @关注 
     */
    public function subEvent(){
        $newsData['items'] =array(
            array(
                'title'=>'欢迎关注图腾贷公众帐号',
                'description'=>'图腾贷是中国领先的互联网金融P2P理财平台，为投资理财用户提供透明、安全、高效的互联网金融P2P理财服务。投资理财用户可通过图腾贷p2p理财平台进行投标、购买债权等方式进行投资获得安全的高收益!',
                'picurl'=>'http://mmbiz.qpic.cn/mmbiz/RRSQpH7f2WoGLqbc4fupmUEgeM1KAiapfStVR3pjUx0EoYeIMucywkPFicnJEzWrpROOqlx6Y2JHpaOyfwiar3vQg/640?wx_fmt=jpeg&tp=webp&wxfrom=5',
                'url'=>'http://mp.weixin.qq.com/s?__biz=MzAxMDE2NDA5MA==&mid=202936791&idx=2&sn=018668a382cb5acbd7187bf2e1893a84#rd'
            )
        );
        $this->reply = $this->wx->makeNews($newsData);
    }


    /**
     * @自定义菜单点击事件
     */
    public function clickEvent(){
        switch ($this->wx->msg['EventKey']) {
            case "V1001_DAILY_SIGN":
                $this->load->model('Sign_model','sign');
                $content = $this->sign->dosign($this->openid);
                $this->reply = $this->wx->makeText($content);
                break;
        }
    }


    private function _initEvent(){
        switch ($this->event) {
            case "subscribe":
                $this->subEvent();
                break;
            case "VIEW":

                break;
            case 'CLICK':
                $this->clickEvent();
                break;
            case "unsubscribe":

                break;
            case 'TEMPLATESENDJOBFINISH':
                $this->reply='';
                break;
            default:
                $this->reply='';
                break;
        }
    }



    /**
     * @接受事件
     * @param wx 微信对象
     */
    public function receiveEvent($wx){
        /**
         * @第一步：初始化微信对象，
         */
        $this->_init($wx);
        /**
         * @初始化事件
         */
        $this->_initEvent();

        return $this->reply ;
    }

}
