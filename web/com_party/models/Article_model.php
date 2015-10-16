<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Article_model extends Base_model {
    public function __construct()
    {
        parent::__construct('article');
        $this->load->model('article_tag_model'	, 'tag_rel');
    }

    public function _where($where)
    {
        $cat_ids = array();

        if (!empty($where['cat_id'])) {
            $cat_ids[] = intval($where['cat_id']);
        }

        if (!empty($where['children_cat_id'])) {
            $cat_ids = array_merge($cat_ids, $where['children_cat_id']);
        }

        if (!empty($cat_ids)) {
            $this->db->where_in("cat_id", $cat_ids);
        }

        if (!empty($where['title'])) {
            $this->db->like("title", $where['title']);
        }

        if (!empty($where['start_time'])) {
            $this->db->where("publish_time >=", $where['start_time']);
        }

        if (!empty($where['end_time'])) {
            $this->db->where("publish_time <=", "{$where['end_time']} 23:59:59");
        }

        if (isset($where['url_route'])) {
            $this->db->where("url_route IS NOT NULL", NULL, FALSE);
        }

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->pk} desc");
        }

        if (isset($where['ids']) && !empty($where['ids'])) {
            $this->db->where_in('id', $where['ids']);
        }
    }

    /**
     * 获取数据 [多条]
     *
     * @param  array $where	//查询条件
     * @access public
     * @author LEE
     * @return array
     */
    public function getRows($where)
    {
    	if (!empty($where['limit'])) {
    		if (is_array($where['limit'])) {
    			$this->db->limit($where['limit'][1], $where['limit'][0]);
    		} else {
    			$this->db->limit($where['limit']);
    		}
    		unset($where['limit']);
    	}

    	if (!empty($where['cols'])) {
			$this->db->select(implode($where['cols'], ','));
			unset($where['cols']);
		}

        if (isset($where['cat_ids'])) {
            $this->db->where_in("cat_id", $where['cat_ids']);
        }

        unset($where['cat_ids']);

        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
            unset($where['order_by']);
        } else {
            $this->db->order_by("{$this->pk} desc");
        }

        if (!empty($where['not_in'])) {
            foreach ($where['not_in'] as $key=>$value) {
                $this->db->where_not_in($key, $value);
            }
            unset($where['not_in']);
        }

    	$this->db->where($where);
        $query = $this->db->get($this->table);
        return $query->result_array();
    }
}

