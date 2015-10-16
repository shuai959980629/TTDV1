<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 用户消息中心模型
 * zhang xiaojian
 */
class User_news_model extends Base_model {
    public function __construct()
    {
        parent::__construct('user_news');
        $this->pk='id';
    }
    public function setWhere($where=array())
    {
        if (isset($where['order by']) && $where['order by'] != '') {
            $this->db->order_by($where['order by']);
            unset($where['order by']);
        }else{
            $this->db->order_by("{$this->pk} desc");
        }
        if (isset($where['limit'])) {
            $this->db->limit($where['limit']);
            unset($where['limit']);
        }
    }
    /**
     * 存储数据，包含验证是否覆盖消息
     * @param  array  $data     需要存储的数据
     * @return int             失败为null
     */
    public function saveData($data=array())
    {
        if (!isset($data['trans_id'])) {
            return null;
        }
        $trans_id = $data['trans_id'];
    	//通过trans_id检测是否存在该条数据
    	$check = $this->getByTtansId($trans_id);
        if (!isset($data['is_read'])) {
            $data['is_read']=0;
        }
    	if (empty($check)) {
    		return $this->create($data);
    	}else{
    		return $this->update($check[0]['id'],$data);
    	}
    }
    /**
     * 通过trans_id获取记录
     * @param  string $trans_id 
     * @return array           
     */
    public function getByTtansId($trans_id='')
    {
    	$this->_where(array('trans_id'=>$trans_id));
    	$query = $this->db->get($this->table);
        return $query->result_array();
    }
}

