<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ad_position_model extends Base_model {

    public function __construct()
    {
        parent::__construct('ad_position');
    }

    public function remove($id)
    {
        $this->load->model('Ad_model', 'ad');
        $this->ad->rm_by_position($id);

        $result = parent::remove($id);

        return $result;
    }

    public function _where($where)
    {
        if (isset($where['ids']) && !empty($where['ids'])) {
            $this->db->where_in($this->pk, $where['ids']);
        }

        if (isset($where['title']) && !empty($where['title'])) {
            $this->db->like('title', $where['title']);
        }
    }
}
