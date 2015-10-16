<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_model extends CI_Model {

    public  $table = NULL;
    public     $pk = 'id';

    public function __construct($table = NULL)
    {
        parent::__construct();

        $this->table = $table;
        
    }

    public function save($data, $id = 0)
    {
        if ((int) $id > 0) {
            return $this->update($id, $data);
        }
        else {
            return $this->create($data);
        }
    }

    public function create($data)
    {
        $this->db->insert($this->table, $data);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }
        else {
            return NULL;
        }
    }

    public function remove($id)
    {
        if (is_int($id)) {
            $id = array($id);
        }

        $this->db->where_in($this->pk, $id)
            ->delete($this->table);

        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    public function update($id, $data)
    {
        $this->db->where($this->pk, (int) $id)
            ->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return $id;
        }
        else {
            return NULL;
        }
    }

    public function _where($where)
    {
        if (isset($where['order_by']) && !empty($where['order_by'])) {
            $this->db->order_by($where['order_by']);
            unset($where['order_by']);
        }
        else {
            $this->db->order_by("{$this->table}.{$this->pk} desc");
        }

        $this->db->where($where);
    }

    public function count_all($where = array())
    {
    	$this->db->_count_string = "SELECT COUNT({$this->pk}) AS ";
        $this->_where($where);
        return $this->db->count_all_results($this->table);
    }

    protected function _select()
    {
        $this->db->select("{$this->table}.*");
    }

    public function search($where = array(), $limit = 20, $offset = 0)
    {
        if (!empty($offset)) {
            $ids = $this->_find_ids($where, $limit, $offset);
            $this->db->where_in($this->pk, $ids);
        }
        else {
            $this->db->limit($limit);
            $this->_where($where);
        }

        $this->_select();

        $query = $this->db->get($this->table);

        return $query->result_array();
    }

    public function get($pk)
    {
        $where = array(
            "{$this->table}.{$this->pk}" => (int) $pk,
        );

        $query = $this->db->get_where($this->table, $where);

        return $query->row_array();
    }

    public function all($where = array())
    {
        $this->_select();
        if ($where) {
            $query = $this->db->get_where(
                $this->table,
                $where
            );
        }
        else {
            $query = $this->db->get($this->table);
        }

        return $query->result_array();
    }

    private function _find_ids($where, $limit, $offset)
    {
        $this->db->select("{$this->table}.{$this->pk}");
        $this->_where($where);
        $this->db->limit($limit, $offset);

        $query = $this->db->get($this->table);

        $ids = array();

        if ($query->num_rows() > 0) {
            $rows = $query->result_array();
            foreach ($rows as $row) {
                $ids[] = $row['id'];
            }
        }

        return $ids;
    }
    
    /**
     * where条件生成器
     * 
     * @param  array $where //查询条件
     * @access public
     * @author LEE
     * @copyright 20150817
     * @return void
     */
    public function dbWhere($where)
    {
    	if (!empty($where['cols'])) {
			is_array($where['cols']) 
				? $this->db->select(implode($where['cols'], ','))
				: $this->db->select($where['cols']);
			unset($where['cols']);
		}
		
        if (!empty($where['scope'])) {
        	foreach ($where['scope'] as $key=>$value) {
        		foreach ($value as $k=>$val) {
        			$key == 'lt'  && $this->db->where($k . ' > ' , $val);
	        		$key == 'ltt' && $this->db->where($k . ' >= ', $val);
	        		$key == 'mt'  && $this->db->where($k . ' < ' , $val);
	        		$key == 'mtt' && $this->db->where($k . ' <= ', $val);
        		}
        	}
        	unset($where['scope']);
        }
        
        if (!empty($where['in'])) {
        	foreach ($where['in'] as $key=>$value) {
        		$this->db->where_in($key, $value);
        	}
        	unset($where['in']);
        }
        
        if (!empty($where['not_in'])) {
        	foreach ($where['not_in'] as $key=>$value) {
        		$this->db->where_not_in($key, $value);
        	}
        	unset($where['not_in']);
        }
        
        if (!empty($where['like'])) {
        	foreach ($where['like'] as $key=>$value) {
        		//value[1] = before 、 after or both
        		if (is_array($value)) {
        			!empty($value[1]) 
        				? $this->db->like($key, $value[0], $value[1])
        				: $this->db->like($key, $value[0]);
        		} else {
        			$this->db->like($key, $value);
        		}
			}
        	unset($where['like']);
        }
        
        if (!empty($where['sum'])) {
        	$this->db->select_sum($where['sum']);
        	unset($where['sum']);
        }
        
        if (!empty($where['order'])) {
        	$order = array();
        	foreach ($where['order'] as $key=>$value) {
        		$order[] = $key . ' ' . $value;
        	}
            $this->db->order_by(implode($order, ','));
            unset($where['order']);
        }
        
        if (!empty($where['custom'])) {
        	$this->db->where($where['custom']);
            unset($where['custom']);
        }
        
        if (!empty($where['eq'])) {
        	$this->db->where($where['eq']);
        	unset($where['eq']);
        }
        
        if (!empty($where)) {
        	$this->db->where($where);
        }
    }
}