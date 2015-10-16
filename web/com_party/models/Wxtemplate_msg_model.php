<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @微信模板消息
 * @author zhoushuai
 * @license		图腾贷 [手机端，PC]
 * @category 2015-09-28
 * @version
 */
class Wxtemplate_msg_model extends CI_model
{

    private $hostWx;
    private $template_id;
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->load->library('WxService');
        $this->load->model('bind_model','bind');
        $this->load->model('user_model','user');
    }

    private function init(){
        $this->load->config('wxTemple');
        $common =$this->config->item('common');
        $wxTemple = $this->config->item(ENVIRONMENT);
        $this->template_id = $wxTemple['template_id'];
        $this->hostWx = $common['hostWxComplete'];
    }


    public function sendWxTemple($data){
        if(empty($data['mobile'])){
            $rturn = array('status'=>0,'msg'=>'手机号为空','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $user = $this->user->get_uid_by_mobile($data['mobile']);
        if(empty($user)){
            $rturn = array('status'=>0,'msg'=>'用户'.$data['mobile'].'不存在','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $data['uid'] = $user['uid'];
        if (method_exists($this, $data['method']))
        {
            return $this->$data['method']($data);
        }else{
            $rturn = array('status'=>0,'msg'=>'方法'.$data['method'].'不存在','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
    }



    /**
     * @错误记录。方便调试
     */
    private function logWxTemple($data){
        $log = '
#===============================================================================================================
#DEBUG-WxTemplate(微信模版消息) start |执行时间：%s (ms)
#===============================================================================================================
#请求接口:%s
#User-Agent:%s
#返回数据(Array):
$data=%s;
#==================================================debug end====================================================' .
            "\r\n\r\n";
        debug_log($log, $data);
    }


    /**
     * @验证码
     * @param  code 手机动态验证码
     * @param  uid  用户uid
     * @param  handle 操作描述（如：修改密码、找回密码、删除银行卡等操作）
     * @param  type<handle:操作验证码提醒,default:验证码下发通知>
     * @return array
     * @example:$data = array('code'=>'', 'uid'=>'','handle'=>'','type'=>'');
     */
    public function code($data){
        $where  = array('uid'=>$data['uid'], 'third_party'=>'weixin');
        $bind   = $this->bind->isBind($where);
        if(!$bind){
            $rturn = array('status'=>0,'msg'=>'用户：['.$data['uid'].']没有绑定微信帐号','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $url = '';
        $template_id = '';
        $type = empty($data['type'])?'':$data['type'];
        switch($type){
            case 'handle':
                //操作验证码提醒
                $template_id = $this->template_id['codeAction'];//模板id
                $tempdata = array(
                    "first"=>array(
                        'value'=>'尊敬的用户,您当前操作的验证码如下：',
                        'color'=>"#173177"
                    ),
                    'keyword1'=>array(
                        'value'=>$data['handle'],//操作（如：修改密码）
                        'color'=>'#173177'
                    ),
                    'keyword2'=>array(
                        'value'=>$data['code'],
                        'color'=>'#173177'
                    ),
                    'remark'=>array(
                        'value'=>'验证码将在1分钟后失效，请尽快完成当前操作。',
                        'color'=>"#173177"
                    )
                );
                break;
            default:
                //验证码下发通知
                $template_id = $this->template_id['code'];//模板id
                $tempdata = array(
                    "first"=>array(
                        'value'=>'尊敬的用户',
                        'color'=>"#173177"
                    ),
                    'number'=>array(
                        'value'=>$data['code'],
                        'color'=>'#173177'
                    ),
                    'remark'=>array(
                        'value'=>'该验证码有效期5分钟可输入1次，转发无效。',
                        'color'=>"#173177"
                    )
                );
                break;
        }
        $res=$this->wxservice->sendTemplateMsg($bind['openid'],$template_id,$url,$tempdata);
        if($res['status']){
            $rturn =  array('status'=>1,'msg'=>'Send weixin Code TemplateMsg succeed!','data'=>$res['data']);
            $this->logWxTemple($rturn);
            return $rturn;
        }else{
            $rturn =  array('status'=>0,'msg'=>'Send weixin Code TemplateMsg Failed!','data'=>$res['msg']);
            $this->logWxTemple($rturn);
            return $rturn;
        }
    }



    /**
     * @提现通知
     * @param  用户uid: uid
     * @param 充值订单 ticket_id
     * @param 当前状态：status
     * @param 提现金额：amount
     * @param 实际到账金额：money
     * @param 手续费：fee
     * @param 时间：time
     * @return  array
     * @example:$data = array('uid'=>'6662','ticket_id'=>'','status'=>'','amount'=>'','money'=>'100','fee'=>'3.21','time'=>'');
     */
    public function cash($data){
        $where  = array('uid'=>$data['uid'], 'third_party'=>'weixin');
        $bind   = $this->bind->isBind($where);
        if(!$bind){
            $rturn = array('status'=>0,'msg'=>'用户：['.$data['uid'].']没有绑定微信帐号','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }

        $this->load->model('user_account_log_model','user_account_log');
        $where = array('rel_type'=>'cash','uid'=>$data['uid'],'ticket_id'=>$data['ticket_id']);
        $user_account = $this->user_account_log->getWidgetRow($where);
        if(empty($user_account)){
            $rturn = array('status'=>0,'msg'=>"该订单ID[{$data['ticket_id']}]充值流水，不存在或丢失！",'data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }

        //获取用户真实姓名
        $this->load->model('user_identity_model','identity');
        $where  = array('uid'=>$data['uid'], 'status'=>'allow');
        $identity= $this->identity->getWidgetRow($where);


        $url = $this->hostWx.'fund/detail?id='.$user_account['id'];//详情地址(提现记录)
        $tempdata = array(
            "first"=>array(
                'value'=>'亲爱的'.$identity['realname'].'，您正在申请提现：',
                'color'=>"#173177"
            ),
            //当前状态
            'keyword1'=>array(
                'value'=>$data['status'],
                'color'=>"#173177"
            ),
            //提现金额
            'keyword2'=>array(
                'value'=>$data['amount'].'元',
                'color'=>"#173177"
            ),
            //手续费
            'keyword3'=>array(
                'value'=>$data['fee'].'元',
                'color'=>"#173177"
            ),
            //实际到账金额
            'keyword4'=>array(
                'value'=>$data['money'].'元',
                'color'=>"#173177"
            ),
            //时间
            'keyword5'=>array(
                'value'=>$data['time'],
                'color'=>"#173177"
            ),
            'remark'=>array(
                'value'=>'16点之前的申请，当天处理。具体到账以银行为准。',
                'color'=>"#173177"
            )
        );
        $res=$this->wxservice->sendTemplateMsg($bind['openid'],$this->template_id['cash'],$url,$tempdata);
        if($res['status']){
            $rturn =  array('status'=>1,'msg'=>'Send weixin Cash TemplateMsg succeed!','data'=>$res['data']);
            $this->logWxTemple($rturn);
            return $rturn;
        }else{
            $rturn =  array('status'=>0,'msg'=>'Send weixin Cash TemplateMsg Failed!','data'=>$res['msg']);
            $this->logWxTemple($rturn);
            return $rturn;
        }
    }



    /**
     * @充值通知
     * @param openid 用户微信openid
     * @param 充值方式: mod payment_code
     * @param 充值金额：amount
     * @param 充值状态：status\
     * @param 订单编号: order_no
     * @return  array
     * @example:$data = array('openid'=>'', 'mod'=>'','amount'=>'','status'=>'');
     * $openid = 'oenVrsxAC8EMfEJY-J4yyE3bOl6I';
     */
    public function recharge($order_no){
        if(empty($order_no)){
            $rturn = array('status'=>0,'msg'=>'订单编号为空','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }

        $this->load->model('recharge_model','recharge');
        $where  = array('order_no'=>$order_no);
        $rorder = $this->recharge->getWidgetRow($where);//充值订单
        if(empty($rorder)){
            $rturn = array('status'=>0,'msg'=>"该订单号[{$order_no}],不存在或丢失！",'data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $rorder['status'] = $this->recharge->state[$rorder['status']];
        $rorder['payment_code']=$this->recharge->payment_code[$rorder['payment_code']];

        $where  = array('uid'=>$rorder['uid'], 'third_party'=>'weixin');
        $bind   = $this->bind->isBind($where);
        if(!$bind){
            $rturn = array('status'=>0,'msg'=>'用户：['.$rorder['uid'].']没有绑定微信帐号','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }

        $this->load->model('user_account_log_model','user_account_log');
        $where = array('rel_type'=>'recharge','uid'=>$rorder['uid'],'ticket_id'=>$rorder['id']);
        $user_account = $this->user_account_log->getWidgetRow($where);
        if(empty($user_account)){
            $rturn = array('status'=>0,'msg'=>"该订单号[{$order_no}]充值流水，不存在或丢失！",'data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }

        $url = $this->hostWx.'fund/detail?id='.$user_account['id'];//充值记录
        $tempdata = array(
            "first"=>array(
                'value'=>'您好，您正在使用'.$rorder['payment_code'].'充值：',
                'color'=>"#173177"
            ),
            'accountType'=>array(
                'value'=>'充值方式',
                'color'=>'#000'
            ),
            'account'=>array(
                'value'=>$rorder['payment_code'],
                'color'=>'#173177'
            ),
            //充值金额
            'amount'=>array(
                'value'=>$rorder['amount'].'元',
                'color'=>"#173177"
            ),
            //充值状态
            'result'=>array(
                'value'=>$rorder['status'],
                'color'=>"#173177"
            ),
            'remark'=>array(
                'value'=>'根据充值渠道不同，到账时间可能有延迟，请耐心等待！',
                'color'=>"#173177"
            )
        );
        $res=$this->wxservice->sendTemplateMsg($bind['openid'],$this->template_id['recharge'],$url,$tempdata);
        if($res['status']){
            $rturn =  array('status'=>1,'msg'=>'Send weixin Recharge TemplateMsg succeed!','data'=>$res['data']);
            $this->logWxTemple($rturn);
            return $rturn;
        }else{
            $rturn =  array('status'=>0,'msg'=>'Send weixin Recharge TemplateMsg Failed!','data'=>$res['msg']);
            $this->logWxTemple($rturn);
            return $rturn;
        }
    }

    /**
     * @投资成功通知
     * @param  用户uid: uid
     * @param  borrow_no标的流水号
     * @param  投资金额：money
     * @param  预计收益：income
     * @param  投资详情 tender_id
     * @return array
     * @example:$data = array('uid'=>6662,'borrow_no'=>'NO.2014091614062913','money'=>'100','income'=>'3.21');
     */
    public function invest($data){
        $where  = array('uid'=>$data['uid'], 'third_party'=>'weixin');
        $bind   = $this->bind->isBind($where);
        if(!$bind){
            $rturn = array('status'=>0,'msg'=>'用户：['.$data['uid'].']没有绑定微信帐号','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }

        if(empty($data['borrow_no'])){
            $rturn = array('status'=>0,'msg'=>"标的流水号不能为空",'data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $_sn = explode('.',$data['borrow_no']);
        $sn = end($_sn);

        $this->load->model('borrow_model','borrow');
        $where = array('sn'=>$sn);
        $borrow = $this->borrow->getWidgetRow($where);
        if(empty($borrow)){
            $rturn = array('status'=>0,'msg'=>"标[{$sn}]不存在",'data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $borrow['period'] = $this->borrow->period[$borrow['period']];
        $borrow['repay_type'] = $this->borrow->repay_type[$borrow['repay_type']];

        $tenderID  = empty($data['tender_id'])?'':$data['tender_id'];
        $url = $this->hostWx.'invest/detail/'.$tenderID;
        $tempdata = array(
            "first"=>array(
                'value'=>'您已成功投资，明日开始计息。',
                'color'=>"#173177"
            ),
            //项目名称
            'keyword1'=>array(
                'value'=>$borrow['title'],
                'color'=>"#173177"
            ),
            //年化收益
            'keyword2'=>array(
                'value'=>$borrow['apr'].'%',
                'color'=>"#173177"
            ),
            //项目期限
            'keyword3'=>array(
                'value'=>$borrow['period'],
                'color'=>"#173177"
            ),
            //投资金额
            'keyword4'=>array(
                'value'=>$data['money'].'元',
                'color'=>"#173177"
            ),
            //预计收益
            'keyword5'=>array(
                'value'=>$data['income'].'元',
                'color'=>"#173177"
            ),
            'remark'=>array(
                'value'=>'如您有任何问题，欢迎致电(400-007-9028)询问或联系客服。',
                'color'=>"#173177"
            )
        );
        $res=$this->wxservice->sendTemplateMsg($bind['openid'],$this->template_id['invest'],$url,$tempdata);
        if($res['status']){
            $rturn =  array('status'=>1,'msg'=>'Send weixin Invest TemplateMsg succeed!','data'=>$res['data']);
            $this->logWxTemple($rturn);
            return $rturn;
        }else{
            $rturn =  array('status'=>0,'msg'=>'Send weixin Invest TemplateMsg Failed!','data'=>$res['msg']);
            $this->logWxTemple($rturn);
            return $rturn;
        }
    }


    /**
     *@param 项目还款通知
     *@param 项目名称 title
     *@param 还款本金 money
     *@param 还款利息 income
     *@param 还款时间 time
     *@param 还款方式  repay_type
     *@param 还款详情 tender_id
     *@param 标的流水号 borrow_no
     *@return  array
     *@example:$data = array('uid'=>'6662','tender_id'=>'101','borrow_no'=>'NO.2014091614062913','money'=>'100','income'=>'3.21','time'=>date('Y-m-d H:i:s',time()));
     */
    public function repay($data){
        $where  = array('uid'=>$data['uid'], 'third_party'=>'weixin');
        $bind   = $this->bind->isBind($where);
        if(!$bind){
            $rturn = array('status'=>0,'msg'=>'用户：['.$data['uid'].']没有绑定微信帐号','data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }

        if(empty($data['borrow_no'])){
            $rturn = array('status'=>0,'msg'=>"标的流水号不能为空",'data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $_sn = explode('.',$data['borrow_no']);
        $sn = end($_sn);

        $this->load->model('borrow_model','borrow');
        $where = array('sn'=>$sn);
        $borrow = $this->borrow->getWidgetRow($where);
        if(empty($borrow)){
            $rturn = array('status'=>0,'msg'=>"标[{$sn}]不存在",'data'=>null);
            $this->logWxTemple($rturn);
            return $rturn;
        }
        $borrow['period'] = $this->borrow->period[$borrow['period']];
        $borrow['repay_type'] = $this->borrow->repay_type[$borrow['repay_type']];


        $tenderID  = empty($data['tender_id'])?'':$data['tender_id'];
        $url = $this->hostWx.'invest/detail/'.$tenderID;
        $tempdata = array(
            "first"=>array(
                'value'=>'您投资的项目有还款，请注意查收:',
                'color'=>"#173177"
            ),
            //项目名称
            'keyword1'=>array(
                'value'=>$borrow['title'],
                'color'=>"#173177"
            ),
            //还款本金
            'keyword2'=>array(
                'value'=>$data['money'].'元',
                'color'=>"#173177"
            ),
            //还款利息
            'keyword3'=>array(
                'value'=>$data['income'].'元',
                'color'=>"#173177"
            ),
            //还款时间
            'keyword4'=>array(
                'value'=>$data['time'],
                'color'=>"#173177"
            ),
            //还款方式
            'keyword5'=>array(
                'value'=>$borrow['repay_type'],
                'color'=>"#173177"
            ),
            'remark'=>array(
                'value'=>'如您有任何问题，欢迎致电(400-007-9028)询问或联系客服。',
                'color'=>"#173177"
            )
        );
        $res=$this->wxservice->sendTemplateMsg($bind['openid'],$this->template_id['repay'],$url,$tempdata);
        if($res['status']){
            $rturn =  array('status'=>1,'msg'=>'Send weixin Repay TemplateMsg succeed!','data'=>$res['data']);
            $this->logWxTemple($rturn);
            return $rturn;
        }else{
            $rturn = array('status'=>0,'msg'=>'Send weixin Repay TemplateMsg Failed!','data'=>$res['msg']);
            $this->logWxTemple($rturn);
            return $rturn;
        }

    }



}