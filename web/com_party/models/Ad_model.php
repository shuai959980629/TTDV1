<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ad_model extends Base_model {

    public $media_type = array(
        'img'   => '图片',
        'flash' => 'Flash',
        'code'  => '代码',
        'text'  => '文字',
    );

    public function __construct()
    {
        parent::__construct('ad');
    }

    public function _where($where)
    {
        if (isset($where['position_id']) && !empty($where['position_id'])) {
            $this->db->where('position_id', (int) $where['position_id']);
        }

        if (isset($where['ids']) && !empty($where['ids'])) {
            $this->db->where_in('id', $where['ids']);
        }

        if (isset($where['title']) && !empty($where['title'])) {
            $this->db->like('title', $where['title']);
        }

        if (isset($where['media_type']) && !empty($where['media_type'])) {
            if (is_array($where['media_type'])) {
                $this->db->where_in('media_type', $where['media_type']);
            }
            else {
                $this->db->where('media_type', $where['media_type']);
            }
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }
    }

    public function get_ads_by_position($pid)
    {
    	$where = array('position_id' => $pid);
    	$this->db->where($where);
    	$this->db->order_by("{$this->table}.sort_order desc");
    	$query = $this->db->get($this->table);

        return $query->result_array();
    }

    public function rm_by_position($pid)
    {
        $ids    = array();
        $result = FALSE;

        if (!is_array($pid)) {
            $pid = array($pid);
        }

        foreach ($pid as $v) {
            $ads = $this->get_ads_by_position($pid);
            if (!empty($ads)) {
                foreach ($ads as $ad) {
                    $ids[] = $ad['id'];
                }
            }
        }

        if (!empty($ids)) {
            $result = $this->remove($ids);
        }

        return $result;
    }

    public function remove($id)
    {
        $rows = $this->search(array('ids' => $id));

        if (empty($rows)) {
            return FALSE;
        }

        foreach ($rows as $row) {
            if (($row['media_type'] == 'img' || $row['media_type'] == 'flash') && !empty($row['content'])) {
                $ad_file = APPPATH . "../data/{$row['content']}";
                file_exists($ad_file) && unlink($ad_file);
            }
        }

        return parent::remove($id);
    }

}
