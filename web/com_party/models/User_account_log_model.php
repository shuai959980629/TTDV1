<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_account_log_model extends Base_model {

    public function __construct()
    {
        parent::__construct('user_account_log');
    }

    public function write($data)
    {
        log_message('error',json_encode($data),'account_log');
        $log = $this->_is_exists($data);
        if (empty($log)) {
            $data['id'] = unique_id();
            $id = 0;
        }
        else {
            $log['rel_data'] = json_decode($log['rel_data'], TRUE);
            if (!function_exists('logs_merge')) {
                function logs_merge($a, $b) {
                    foreach ($b as $k => $v) {
                        if (empty($v)) {
                            continue;
                        }

                        /*log_message('debug', "{$k}:" . var_export($v, TRUE));
                        log_message('debug', '$a[k]:' . var_export($a[$k], TRUE));*/
                        if (is_array($v) && $k === 'data') {
                            $a['data'] = $b['data'] + $a['data'];
                        } elseif (is_array($v)) {
                            $a[$k] = logs_merge($a[$k], $v);
                        } else {
                            $a[$k] = $v;
                        }
                    }
                    return $a;
                };
            }
            $data['rel_data'] = logs_merge($log['rel_data'], $data['rel_data']);
            $id = $log['id'];
            unset($data['id']);
        }
        if (!isset($data['created']) || empty($data['created'])) {
            $data['created'] = date('Y-m-d H:i:s');
        }
        $data['rel_data'] = json_encode($data['rel_data']);

        return $this->save($data, $id);
    }

    private function _is_exists($data)
    {
        $log = $this->search(
            array(
                'uid'       => (int) $data['uid'],
                'rel_type'  => $data['rel_type'],
                'ticket_id' => $data['ticket_id'],
            ),
            1
        );

        if (!empty($log)) {
            return $log[0];
        }
        else {
            return array();
        }
    }
    //对应处理今日收益数据
    public function income_today($data=array())
    {
        log_message('error',json_encode(array('step'=>'adddata','data'=>$data)),'today_income_log');
        $new_log=$this->_is_exists($data);
        log_message('error',json_encode(array('step'=>'olddata','data'=>$new_log)),'today_income_log');
        if (empty($new_log)) {
            $new_log = $data;
            $new_log['id'] = unique_id();
            $id = 0;
        }else{
            $new_log['rel_data'] = json_decode($new_log['rel_data'], TRUE);
            if (is_null($new_log['rel_data'])) {
                $new_log['rel_data']=$data['rel_data'];
            }else{
                if (isset($data['rel_data']['account'])) {
                    $new_log['rel_data']['account']=$data['rel_data']['account'];   
                }
                if (isset($data['rel_data']['title'])) {
                    $new_log['rel_data']['title']=$data['rel_data']['title'];
                }
                foreach ($data['rel_data']['logs'] as $key => $value) {
                    $check=$this->getUqieArr($new_log,$value);
                    if (!$check) {
                        $new_log['rel_data']['logs'][]=$value;
                    }
                }
            }
            $id = $new_log['id'];
            unset($new_log['id']);
        }
        log_message('error',json_encode(array('step'=>'newdata','data'=>$new_log)),'today_income_log');
        $new_log['rel_data']['money']=0;
        foreach ($new_log['rel_data']['logs'] as $key => $value) {
            $new_log['rel_data']['money']+=floatval($value['money']);
        }
        $new_log['created'] = date('Y-m-d H:i:s');
        $new_log['rel_data'] = json_encode($new_log['rel_data']);

        return $this->save($new_log, $id);
    }
    //今日收益检查相同数据
    private function getUqieArr(&$data=array(),$new_data=array())
    {
        foreach ($data['rel_data']['logs'] as $key => $value) {
            if (intval($value['tender_id'])==intval($new_data['tender_id']) && intval($value['borrow_id'])==intval($new_data['borrow_id'])) {
                $new_log['rel_data']['logs'][$key]=$value;
                return true;
            }
        }
        return false;
    }
    public function searchPage($where, $limit = 10, $offset = 0)
    {
        $this->dbWhere($where);
        if ($offset > 0) {
            $this->db->limit($limit, $offset);
        }
        elseif ($limit > 0) {
            $this->db->limit($limit);
        }

        $query = $this->db->get($this->table);
        $result = $query->result_array();
        return !empty($result) ? $result : array();
    }
    
    /**
     * 获取数据总数
     *
     * @param  array   $where	//查询条件
     * @access public
     * @author LEE
     * @copyright 20180818
     * @return integer
     */
    public function getTotal($where)
    {
    	$this->dbWhere($where);
    	return $this->count_all($where);
    }
    
    /**
     * 特殊条件组合查询
     *
     * @param  array $where //特殊查询条件
     * @access private
     * @author LEE
     * @return void
     */
    private function dbWhere(&$where)
    {
        if (!empty($where['cols'])) {
            $this->db->select(implode($where['cols'], ','));
            unset($where['cols']);
        }
        
        if (!empty($where['scope'])) {
            foreach ($where['scope'] as $key=>$value) {
            	foreach ($value as $k=>$val) {
            		$key == 'lt'  && $this->db->where($k  . ' > '  , $val);
            		$key == 'ltt' && $this->db->where($k  . ' >= ' , $val);
            		$key == 'mt'  && $this->db->where($k  . ' < '  , $val);
            		$key == 'mtt' && $this->db->where($k  . ' <= ' , $val);
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
        
        if (!empty($where['group'])) {
            foreach ($where['group'] as $key=>$value) {
                $group[] = $key . ' ' . $value;
            }
            $this->db->group_by(implode($group, ','), false);
            unset($where['group']);
        }
        
        if (!empty($where['order'])) {
            $order = array();
            foreach ($where['order'] as $key=>$value) {
                $order[] = $key . ' ' . $value;
            }
            $this->db->order_by(implode($order, ','));
            unset($where['order']);
        }
        
        if (!empty($where)) {
        	$this->db->where($where);
        }
    }
}
