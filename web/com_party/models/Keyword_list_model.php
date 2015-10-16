<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @微信关键字字典
 * @author zhoushuai
 * @license		图腾贷 [手机端]
 * @category 2015-09-17
 * @version
 */
class Keyword_list_model extends Base_model
{

    public function __construct()
    {
        parent::__construct('keyword_list');
    }


    public function _where($where)
    {
        if (isset($where['keyword']) && !empty($where['keyword'])) {
            $this->db->like('keyword', $where['keyword']);
        }

        if (isset($where['category']) && !empty($where['category'])) {
            $this->db->where('category', $where['category']);
        }

        if (isset($where['cat_id']) && !empty($where['cat_id'])) {
            $this->db->where('cat_id', $where['cat_id']);
        }

        if (isset($where['ids']) && !empty($where['ids'])) {
            $this->db->where_in('id', $where['ids']);
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }
    }




    public function remove($id)
    {
        $result = parent::remove($id);
        return $result;
    }

    public function del($id_keyword){
        if (is_int($id_keyword)) {
            $id_keyword = array($id_keyword);
        }
        $this->db->where_in('id_keyword', $id_keyword)->delete($this->table);
    }


    public function insert_batch($data){
        $this->db->insert_batch($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }else {
            return false;
        }
    }

    public function insert_replace($data){
        $this->db->replace($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }else {
            return false;
        }
    }


    public function update($id,$data)
    {
        $result = $this->db->where($this->pk, (int) $id)->update($this->table, $data);
        if($result){
            return true;
        }else{
            return false;
        }
    }


    public function addKeywordList($data){
        $this->del($data['id_keyword']);
        $keyword = array_unique(array_filter(mb_split('[,，\t\r\n\s]+',$data['keyword'])));
        foreach($keyword as $Key=>$value){
            $dta=array(
                'id_keyword'=>$data['id_keyword'],
                'keyword'=>strtolower($value),
                'cat_id'=>$data['cat_id'],
                'category'=>$data['category']
            );
            $this->insert_replace($dta);
        }
    }



    /**
     * @获取关键字列表
     */
    public function query_list_keyword($where=array(),$limit = 50, $offset = 0){
        $keywordlist = $this->search($where,$limit,$offset);
        if (!empty($keywordlist)){
            return $keywordlist;
        }else {
            return array();
        }
    }

    /**
     * @统计
     */
    public function count_all_keyword($where){
        return $this->count_all($where);
    }



    /**
     * 获取单条数据
     *
     * @param  array $where	//查询条件
     * @access public
     * @author ZHOUSHUAI
     * @return array
     */
    public function getKeyword($where)
    {
        $this->db->where($where);
        $query = $this->db->get($this->table);
        $result= $query->row_array();
        if(!empty($result)){
            $result['request_count']+=1;
            $data=array('request_count'=>$result['request_count']);
            $this->update($result['id'],$data);
        }
        return $result;
    }


    public function keywordLib($keyword){
        $keyword = strtolower($keyword);
        $keywordlist = getMmemData('keywordlist');
        $keywordLib  = getMmemData('keywordLib');
        if(empty($keywordlist)){
            $keywordlist = $this->all($where = array(),array('id_keyword','keyword','cat_id','category'));
            if(!empty($keywordlist)){
               setMmemData('keywordlist',$keywordlist,false,0);
            }else{
                return array('status'=>0,'msg'=>'请在后台添加关键字，并更新入库！','data'=>null);
            }
        }
        if(empty($keywordLib)){
            $keywordLib = array();
            foreach($keywordlist as $Ky=>$value){
                $keywordLib[] = $value['keyword'];
            }
            setMmemData('keywordLib',$keywordLib,false,0);
        }
        sort($keywordLib,SORT_STRING);
        $kword = '';
        foreach($keywordLib as $key=>$item){
            $dt[] = array(
                'keyword'=>$keyword,
                'item'=>$item
            );
            $result = mb_stripos($keyword,$item);
            if($result!==false){
                $kword = $item;
                break;
            }
        }
        if(empty($kword)){
            return array('status'=>1,'msg'=>null,'data'=>null);
        }else{
            $kwordArr = array();
            foreach($keywordlist as $ky=>$values){
                if(in_array($kword,$values)){
                    $kwordArr = $values;
                    break;
                }
            }
            return array('status'=>1,'msg'=>null,'data'=>$kwordArr);
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
#DEBUG-Keywords(关键字LIB) start |执行时间：%s (ms)
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
