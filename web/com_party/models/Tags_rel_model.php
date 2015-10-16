<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tags_rel_model extends Base_model {
    public function __construct()
    {
        parent::__construct('tags_rel');
    }

    public function _where($where)
    {
        if (!empty($where['from_tag_id']) && !empty($where['to_tag_id'])) {
            $this->db
                ->group_start()
                ->where("from_tag_id", (int) $where['from_tag_id'])
                ->where("to_tag_id", (int) $where['to_tag_id'])
                ->group_end()
                ->or_group_start()
                ->where("from_tag_id", (int) $where['to_tag_id'])
                ->where("to_tag_id", (int) $where['from_tag_id'])
                ->group_end()
            ;
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }
    }

    public function is_exists($from_tag_id, $to_tag_id)
    {
        $from_tag_id = (int) $from_tag_id;
        $to_tag_id = (int) $to_tag_id;

        if ($from_tag_id <= 0 || $to_tag_id <= 0) {
            return FALSE;
        }

        $result = $this->count_all(
            array(
                'from_tag_id' => $from_tag_id,
                'to_tag_id' => $to_tag_id,
            )
        );

        if (!empty($result)) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    public function remove_by_tag($tid)
    {
        $result = $this->db->where_in("from_tag_id", $tid)
                           ->or_where_in("to_tag_id", $tid)
                           ->delete($this->table);
        return $result;
    }

    public function create($data)
    {
        $this->db->replace($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }
        else {
            return NULL;
        }
    }
}

