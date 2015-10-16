<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @微信关键字
 * @author zhoushuai
 * @license		图腾贷 [手机端]
 * @category 2015-09-17
 * @version
 */
class Keyword_model extends Base_model
{

    public function __construct()
    {
        parent::__construct('keyword');
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


    public function getCatIds($ids)
    {
        $this->db->select('cat_id');
        $this->db->where_in('id', $ids);
        $query = $this->db->get($this->table);
        return $query->result_array();
    }






    public function remove($id)
    {
        $result = parent::remove($id);
        return $result;
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





}
