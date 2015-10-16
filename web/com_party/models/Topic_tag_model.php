<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Topic_tag_model extends Base_model {
    public function __construct()
    {
        parent::__construct('topic_tag_rel');
        $this->load->model('tags_model', 'tags');
    }

    public function _where($where)
    {
        if (!empty($where['topic_id'])) {
            $this->db->where("topic_id", (int) $where['topic_id']);
        }

        if (!empty($where['tag_id'])) {
            $this->db->where("tag_id", (int) $where['tag_id']);
        }

        if (!empty($where['topic_ids'])) {
            $this->db->where_in("topic_ids", $where['topic_ids']);
        }

        if (!empty($where['tag_ids'])) {
            $this->db->where_in("tag_ids", $where['tag_ids']);
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }
    }

    public function remove_by_tags($tags)
    {
        $this->db->where_in('tag_id', $tags);
        $this->db->delete($this->table);

        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    public function remove_by_topic($topics)
    {
        $this->db->where_in('topic_id', $topics);
        $this->db->delete($this->table);

        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    public function unbind($topic_id = 0, $tags = array())
    {
        if ((int) $topic_id <= 0 || empty($tags) || !is_array($tags)) {
            return FALSE;
        }

        $this->db->where('topic_id', (int) $topic_id)
                 ->where_in('tag_id', $tags);

        $this->db->delete($this->table);

        if ($this->db->affected_rows() > 0) {
            $this->tags->appoint($tags, '-1');
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    private function _merge_rel($topic_id, $keywords)
    {
        if ($topic_id <= 0 || empty($keywords)) {
            return FALSE;
        }

        $rows = $this->tags->all(
            array('keywords' => $keywords)
        );
        $tags = array();

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $tags[] = $row['id'];
            }
        }

        log_message('debug', "tags:" . var_export($tags, true));

        $_has_rel = array();

        $result = array(
            'unbind'  => array(),
            'binding' => array(),
        );

        $rows = $this->all(
            array('topic_id' => (int) $topic_id)
        );

        log_message('debug', "tag rel:" . var_export($rows, true));

        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (in_array($row['tag_id'], $tags)) {
                    $_has_rel[] = $row['tag_id'];
                }
                else {
                    $result['unbind'][] = $row['tag_id'];
                }
            }
        }

        log_message('debug', "result['unbind']:" . var_export($result['unbind'], true));
        log_message('debug', "_has_rel:" . var_export($_has_rel, true));

        if (empty($_has_rel)) {
            $result['binding'] = $tags;
        }
        else {
            $result['binding']    = array_diff($tags, $_has_rel);
        }

        log_message('debug', "result:" . var_export($result, true));

        return $result;
    }

    public function binding($topic_id, $tags)
    {
        if (empty($tags) || (int) $topic_id <= 0) {
            return FALSE;
        }

        $data = array();
        foreach ($tags as $tag) {
            $data[] = array(
                'tag_id'        => (int) $tag,
                'topic_id'    => (int) $topic_id,
            );
        }

        $result = $this->db->insert_batch($this->table, $data);

        $this->tags->appoint($tags);

        return $result;
    }

    public function build_rel($topic_id, $tags)
    {
        if ((int) $topic_id <= 0 || empty($tags) || !is_array($tags)) {
            return FALSE;
        }

        $_rel = $this->_merge_rel($topic_id, $tags);
        $this->unbind($topic_id, $_rel['unbind']);
        $this->binding($topic_id, $_rel['binding']);
    }
}

