<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @微信素材管理
 * @author zhoushuai
 * @license		图腾贷
 * @category 2015-09-22
 * @version
 */
class Material_model extends Base_model
{

    public function __construct()
    {
        parent::__construct('material');
    }


    public function _where($where)
    {
        if (isset($where['title']) && !empty($where['title'])) {
            $this->db->like('title', $where['title']);
        }

        if (isset($where['media_id']) && !empty($where['media_id'])) {
            $this->db->where('media_id', $where['media_id']);
        }

        if (isset($where['ids']) && !empty($where['ids'])) {
            $this->db->where_in('id', $where['ids']);
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        } else {
            $this->db->order_by("{$this->pk} desc");
        }
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


    public function remove($id)
    {
        $result = parent::remove($id);
        return $result;
    }


    /**
     * @获取关键字列表
     */
    public function query_list_material($where=array(),$limit = 50, $offset = 0){
        $materiallist = parent::search($where,$limit,$offset);
        if (!empty($materiallist)){
            return $materiallist;
        }else {
            return array();
        }
    }

    /**
     * @统计
     */
    public function count_all_material($where){
        $result = parent::count_all($where);
        return $result;
    }

    /**
     * 获取单条数据
     *
     * @param  array $where	//查询条件
     * @access public
     * @author ZHOUSHUAI
     * @return array
     */
    public function getmaterial($where)
    {
        $this->db->where($where);
        $query = $this->db->get($this->table);
        $result= $query->row_array();
        return $result;
    }







}
