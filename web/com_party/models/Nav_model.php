<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Nav_model extends Base_model {
    public $target = array(
        '_self'     => '当前窗口',
        '_blank'    => '新窗口',
    );

    public $disp_position = array(
        'top'       => '页头',
        'nav_bar'   => '主导航',
        'footer'    => '页脚'
    );

    public function __construct()
    {
        parent::__construct('nav');
    }

    public function _where($where = array())
    {
        $this->db->order_by("sort_order desc")
            ->order_by("{$this->pk} desc");

        if (!empty($where['disp_position'])) {
            $this->db->where('disp_position', $where['disp_position']);
        }
    }
}
