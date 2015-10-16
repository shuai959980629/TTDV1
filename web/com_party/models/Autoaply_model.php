<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @微信自动回复业务逻辑
 * @author zhoushuai
 * @license		图腾贷 [手机端]
 * @category 2015-08-13
 * @version
 */
class Autoaply_model extends CI_model
{
    private $wx; //微信对象
    private $openid;
    private $keywords; //关键字
    private $reply = '';
    private $content='';


    public function __construct()
    {
        parent::__construct();
        $this->load->model('keyword_model'	, 'keyword');
        $this->load->model('keyword_list_model'	, 'keyword_list');
        $this->load->model('material_model','material');
        $this->load->model('material_text_model', 'material_txt');
        $this->load->model('material_img_model','material_img');
    }

    private function _init($wx){
        $this->wx = $wx;
        $this->openid = $this->wx->openid;
        $this->keywords = $this->wx->msg['Content'];
    }

    /**
     * @关键字回复
     * @param wx 微信对象
     */
    public function  autoAply($wx){
        /**
         * @第一步：初始化微信对象，
         */

        $this->_init($wx);

        /**
         * @第二步：匹配关键字。进行业务处理：
         * @关键字匹配。
         */
        $kwordLib=$this->keyword_list->keywordLib($this->keywords);
        if(!$kwordLib['status'] || empty($kwordLib['data'])){
            $this->debuglog($kwordLib);
            $this->content = "您好，欢迎您关注图腾贷！";
            $this->reply = $this->wx->makeText($this->content);
        }else{
            $where=array(
                'id'=>$kwordLib['data']['id_keyword'],
                'category'=>$kwordLib['data']['category'],
                'cat_id'=>$kwordLib['data']['cat_id']
            );
            $result =$this->keyword->getKeyword($where);
            if(empty($result)){
                $this->content = "您好，欢迎您关注图腾贷！";
                $this->reply = $this->wx->makeText($this->content);
            }else{
                $category = $result['category'];
                switch($category){
                    case 'article':
                        $this->replyNews($result['cat_id']);
                        break;
                    case 'action':
                        $this->content = "您好，欢迎您关注图腾贷。";
                        $this->reply = $this->wx->makeText($this->content);
                        break;
                    case 'text':
                        $this->replyText($result['cat_id']);
                        break;
                }
            }
        }

        /**
         * @第三步：根据业务返回。最终回复 ，返回给微信服务
         */
        return $this->reply;
    }

    /**
     * @图文回复
     * @cat_id 内容id
     */
    private function replyNews($cat_id){
        $article=$this->material->get($cat_id);
        if(!empty($article)){
            $this->load->library('WxService');
            $material = json_decode($article['content'],true);
            $items=array();
            foreach($material as $key =>$list){
                $where = array('media_id'=>$list['picurl']);
                $entity = $this->material_img->getmaterial($where);
                $items[]=array(
                    'title'=>$list['title'],
                    'description'=>$list['description'],
                    'picurl'=>$entity['url'],
                    'url'=>$list['url'],
                );
            }
            $newsData['items']= $items;
            $this->reply = $this->wx->makeNews($newsData);
        }else{
            $this->content = "您好，欢迎您关注图腾贷。";
            $this->reply = $this->wx->makeText($this->content);
        }
    }


    /**
     * @回复纯文本内容
     * @cat_id 内容id
     */
    public function replyText($cat_id){
        $text=$this->material_txt->get($cat_id);
        if(!empty($text)){
            $this->content = $text['content'];
            $this->reply = $this->wx->makeText($this->content);
        }else{
            $this->content = "您好，欢迎您关注图腾贷。";
            $this->reply = $this->wx->makeText($this->content);
        }
    }



    /**
     * 打印日志
     * @param log 日志内容
     */
    protected function debuglog($data)
    {
        $log = '
#===============================================================================================================
#DEBUG-WxService(自动回复) start |执行时间：%s (ms)
#===============================================================================================================
#请求接口:%s
#User-Agent:%s
#返回数据(Array):
$data=%s;
#==================================================debug end====================================================' .
            "\r\n\r\n";
        debug_log($log, $data);
    }


}
