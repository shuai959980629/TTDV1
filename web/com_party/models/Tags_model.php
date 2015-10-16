<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tags_model extends Base_model {
    public function __construct()
    {
        parent::__construct('article_tags');
    }

    public function _where($where)
    {
        if (!empty($where['keyword'])) {
            if (!empty($where['matching']) && $where['matching'] == 'eq') {
                $this->db->where("keyword", $where['keyword']);
            }
            else {
                $this->db
                    ->group_start()
                    ->like("keyword", $where['keyword'])
                    ->or_like("alpha", $where['keyword'])
                    ->group_end();
            }
        }

        if (!empty($where['alpha'])) {
            $this->db->where("alpha", $where['alpha']);
        }


        if (!empty($where['category'])) {
            if ($where['category'] == 'topic') {
                $this->db->where("full_name !=", '-');
            }

            if ($where['category'] == 'tag') {
                $this->db->where("full_name", '-');
            }
        }

        if (!empty($where['keywords']) && is_array($where['keywords'])) {
            $this->db->where_in("keyword", $where['keywords']);
        }

        if (!empty($where['citations'])) {
            $this->db->where('citations >=', $where['citations']);
        }

        if (!empty($where['ids']) && is_array($where['ids'])) {
            $this->db->where_in("id", $where['ids']);
        }

        if (!empty($where['start_time'])) {
            $this->db->where("created >=", $where['start_time']);
        }

        if (!empty($where['end_time'])) {
            $this->db->where("created <=", "{$where['end_time']} 23:59:59");
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }
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

    public function appoint($id, $hits = '+1')
    {
        if (!is_array($id)) {
            $id = array(
                (int) $id
            );
        }

        $this->db->set('citations', "citations{$hits}", FALSE);
        $this->db->where_in('id', $id);
        if ((int) $hits < 0) {
            $this->db->where('citations >', 0);
        }
        $this->db->update($this->table);
        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }
}

