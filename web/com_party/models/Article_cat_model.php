<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Article_cat_model extends Base_Model {

    public function __construct()
    {
        parent::__construct('article_cat');
    }

    public function _where($where)
    {
        if (isset($where['parent_id']) && intval($where['parent_id']) >= 0) {
            $this->db->where('parent_id', (int) $where['parent_id']);
        }

        if (isset($where['ids']) && !empty($where['ids'])) {
            $this->db->where_in($this->pk, $where['ids']);
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }
    }

    /**
     * 获取单条数据
     *
     * @param  array $where	//查询条件
     * @access public
     * @author LEE
     * @return array
     */
    public function getRow($where)
    {
    	$this->db->where($where);
    	$query = $this->db->get($this->table);

    	return $query->row_array();
    }
}
