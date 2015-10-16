<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Article_tag_model extends Base_model {
    public function __construct()
    {
        parent::__construct('article_tag_rel');
        $this->load->model('tags_model', 'tags');
    }

    public function _where($where)
    {
        if (!empty($where['article_id'])) {
            $this->db->where("article_id", (int) $where['article_id']);
        }

        if (!empty($where['tag_id'])) {
            $this->db->where("tag_id", (int) $where['tag_id']);
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

    public function remove_by_articles($articles)
    {
        $query = $this->db->where_in('article_id', $articles)
            ->get($this->table);
        $tags = array(0);
        foreach ($query->result_array() as $row)
        {
            $tags[] = $row['tag_id'];
        }

        $this->db->where_in('article_id', $articles);
        $this->db->delete($this->table);

        if ($this->db->affected_rows() > 0) {
            $this->tags->appoint($tags, '-1');
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    public function unbind($aid = 0, $tags = array())
    {
        if ((int) $aid <= 0 || empty($tags) || !is_array($tags)) {
            return FALSE;
        }

        $this->db->where('article_id', (int) $aid)
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

    private function _merge_rel($aid, $keywords)
    {
        if ($aid <= 0 || empty($keywords)) {
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
            array('article_id' => (int) $aid)
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

    public function binding($aid, $tags)
    {
        if (empty($tags) || (int) $aid <= 0) {
            return FALSE;
        }

        $data = array();
        foreach ($tags as $tag) {
            $data[] = array(
                'tag_id'        => (int) $tag,
                'article_id'    => (int) $aid,
            );
        }

        $result = $this->db->insert_batch($this->table, $data);

        $this->tags->appoint($tags);

        return $result;
    }

    public function build_rel($aid, $tags)
    {
        if ((int) $aid <= 0 || empty($tags) || !is_array($tags)) {
            return FALSE;
        }

        $_rel = $this->_merge_rel($aid, $tags);
        $this->unbind($aid, $_rel['unbind']);
        $this->binding($aid, $_rel['binding']);
    }
}

