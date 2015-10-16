<?php
/**
 * 资金接口 FUNDS CONTROLLER
 *
 * @package	CONTROLLER
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://mrg.tt1.com.cn)
 * @license	http://mrg.tt1.com.cn
 * @link	http://mrg.tt1.com.cn
 * @since	Version 3.0.0 2015-05-15
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Funds extends CI_Controller
{
	/**
	 * 请求方式
	 *
	 * @var string
	 */
	private $mod = 'GET';
	
	/**
	 * 日志文件名
	 *
	 * @var string
	 */
	private $filename = '';
	
	/**
	 * Class constructor
	 *
	 * @return	void
	 */
    public function __construct()
    {
        parent::__construct();
        //获取调用方法
        $segment = $this->uri->segment(2, 0);
        
        $this->load->model('user_account_model', 'user_account');
        $this->load->model('account_log_model' , 'account_log');
        
        $URL = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        $_SERVER['REQUEST_METHOD'] == 'POST' && $this->mod = 'POST';
        if ($this->mod == 'POST') {
        	$param = array();
        	foreach ($_POST as $key=>$value) {
        		if ($key == 'uid') {
        			$uid = explode(',', $value);
        			$param[] = 'sum(uid)=' . (!empty($uid) ? count($uid) : 0);
        		} else {
        			$param[] = $key . '=' . $value;
        		}
        	}
        	$URL .= '?' . implode($param, '&');
        }
        
        $this->filename = 'api-' . $segment . "-" . strtolower($this->mod);
        
        log_message('error', $URL, $this->filename);
        
        header("Content-type: application/json;charset=utf-8");
    }

    /**
     * 操作用户资金主要方法
     *
     * 测试链接：http://test.api.tt1.com.cn/funds/setFunds/?uid=1&money=1000&tob=recharge_success&rel_data_id=1&trans_id=232jdsf3i&pot=20150501
     *
     * @access public
     * @return void
     */
    public function setFunds()
    {
        $fields = $this->input->get(
    		array(
    			'uid'			,	//用户UID
    			'money'			,	//本次操作金额
    			'tob'			,	//本次操作业务类型
    			'rel_data_id'	,	//关联业务记录ID
    			'trans_id'		,	//本次操作唯一码
    			'pot'			,	//本次时间结点
    		)
        );

        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'uid',
                'label' => 'uid',
                'rules' => 'trim|required|is_natural_no_zero',
            ),
            array(
                'field' => 'money',
                'label' => 'money',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'tob',
                'label' => 'tob',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'rel_data_id',
                'label' => 'rel_data_id',
                'rules' => 'trim|required|is_natural_no_zero',
            ),
            array(
                'field' => 'trans_id',
                'label' => 'trans_id',
                'rules' => 'trim|required|alpha_dash',
            ),
            array(
                'field' => 'pot',
                'label' => 'pot',
                'rules' => 'trim|required|is_natural_no_zero',
            ),
        );

        $this->form_validation->set_data($fields);
        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() === FALSE) {
            $error = array(
                'error' => 2,
                'msg'	=> join(',' , $this->form_validation->error_array())
            );
            echo json_encode($error);
            return;
        }

    	$error = array(
    		'error' => 1,
    		'msg'	=> '执行成功'
    	);

    	try {
    		
    		$this->account_log->pot = $fields['pot'];
	    	$error['data'] = $this->account_log->createRow($fields);
	    	
        } catch (TransIdException $e) {
            
            $result = $this->user_account->getRow($fields['uid']);
	    	$error = array(
	    		'error' => 1,
	    		'msg'	=> $e->getMessage(),
	    		'data'	=> $result
	    	);
        } catch (AccountNotFoundException $e) {
        	
            $result = $this->user_account->createUserAccount(array('uid' => (int) $fields['uid']));
            if ($result) {
                $this->setFunds();
            }
            return ;
        } catch (AccountSubtractionException $e) {
        	
        	$result = $this->user_account->getRow($fields['uid']);
        	$error = array(
    			'error' => 2,
    			'msg'	=> $e->getMessage(),
    			'data'	=> $result
    		);
    		
    		$msgSUB  = urldecode(http_build_query($fields)) . " \r\n ";
    		$msgSUB .= urldecode(http_build_query($error));
    		
    		log_message('error', $msgSUB, 'SUB_EXCEPTION');
        } catch (AccountException $e) {
    		$error = array(
    			'error' => 2,
    			'msg'	=> $e->getMessage()
    		);
    	}
    	
    	log_message('error', urldecode(http_build_query($error)), $this->filename);
    	
    	echo json_encode($error);
    	return ;
    }

    /**
     * 获取指定用户资金记录信息
     *
     * @access public
     * @return json
     */
    public function getAccRow($uid = 0)
    {
        $uid = (int) $uid;
        $uid = $uid <= 0 ? (int) $this->input->get('uid') : $uid;

    	$error = array(
    		'error' => 0,
    		'msg'	=> '错误码:0001'
    	);

    	if (empty($uid)) {
    		echo json_encode($error);
    		return;
    	}

    	$result = $this->user_account->getRow($uid);

    	$error = array(
    		'error' => 1,
    		'msg'	=> '执行成功',
    		'data'	=> $result
    	);
    	
    	log_message('error', urldecode(http_build_query($error)), $this->filename);
    	
    	echo json_encode($error);
    	return;
    }

    /**
     * 获取用户资金记录信息列表
     *
     * @access public
     * @return json
     */
    public function getAccSearch()
    {
    	if ($this->input->method() == 'post') {
    		$fields = $this->input->post();
    	} else {
    		$fields = $this->input->get();
    	}
    	
    	$where  = array();
    	$limit  = !empty($fields['limit']) ? $fields['limit'] : 20;
    	$offset = !empty($fields['offset']) ? $fields['offset'] : 0;
    	!empty($fields['uid']) && $where['in'] = array('uid', explode(',', $fields['uid']));
    	
    	$total  = $this->user_account->getTotal($where);
    	
    	!empty($fields['order']) && $where['order'] = $fields['order'];
    	!empty($fields['by']) && $where['by'] = $fields['by'];
    	$result = $this->user_account->getSearch($where, $limit, $offset);
    	
    	$data = array(
    		'result' => $result,
    		'total'	 => $total
    	);

    	$error = array(
    		'error' => 1,
    		'msg'	=> '执行成功',
    		'data'	=> $data
    	);
    	
    	if ($this->input->method() == 'post') {
    		log_message('error', "总返回条数:" . $total, $this->filename);
    	} else {
    		log_message('error', urldecode(http_build_query($error)), $this->filename);
    	}
    	
    	echo json_encode($error);
    	return;
    }

    /**
     * 获取指定资金流水记录信息
     *
     * @access public
     * @return json
     */
    public function getAccLogRow()
    {
    	$fields = $this->input->get(
    		array(
    			'tid'			,	//流水表Trands_ID号
    			'tob'			,	//业务类型
    			'pot'			,	//本次时间结点 [即该条记录的创建时间]
    		)
    	);

    	$error = array(
    		'error' => 0,
    		'msg'	=> '错误码:0001'
    	);

    	if (empty($fields['tid'])) {
    		echo json_encode($error);
    		return;
    	}

    	if (empty($fields['tob'])) {
    		echo json_encode($error);
    		return;
    	}

    	try {
    		$result = $this->account_log->getRow($fields['tid'], $fields['tob'], $fields['pot']);

    		if ($result === null) {
    			$error = array(
		    		'error' => 2,
		    		'msg'	=> '执行成功',
		    		'data'	=> array()
		    	);
    		} else {
    			$error = array(
		    		'error' => 1,
		    		'msg'	=> '执行成功',
		    		'data'	=> $result
		    	);
    		}
    	} catch (Exception $e) {
    		$error = array(
    			'error' => 2,
    			'msg'	=> $e->getMessage()
    		);
    	}
    	
    	log_message('error', urldecode(http_build_query($error)), $this->filename);
    	
    	echo json_encode($error);
    	return;
    }

    /**
     * 获取资金流水记录信息列表
     *
     * @access public
     * @return json
     */
    public function getAccLogSearch()
    {
    	$fields = $this->input->get();
    	$where  = array();
    	$limit  = !empty($fields['limit']) ? $fields['limit'] : 20;
    	$offset = !empty($fields['offset']) ? $fields['offset'] : 0;

    	foreach ($fields as $key=>$value) {
    		if (!in_array($key, array('limit', 'offset'))) {
    			$where[$key] = $value;
    		}
    	}

    	try {
    		$data = $this->account_log->search($where, $limit, $offset);

    		$error = array(
	    		'error' => 1,
	    		'msg'	=> '执行成功',
	    		'data'	=> $data
	    	);
    	} catch (Exception $e) {
    		$error = array(
    			'error' => 2,
    			'msg'	=> $e->getMessage()
    		);
    	}
    	
    	log_message('error', urldecode(http_build_query($error)), $this->filename);
    	
    	echo json_encode($error);
    	return;
    }

    /**
     * 创建一条用户资金总量数据
     *
     * @access public
     * @return json
     */
    public function setUserAccount()
    {
    	$fields = $this->input->get(
    		array(
    			'uid'			,	//用户UID
    			'balance'		,	//可用余额
    			'frozen'		,	//总冻结
    			'await'			,	//总待收
    		)
        );

        $data = array('amount' => 0);

        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'uid',
                'label' => 'uid',
                'rules' => 'trim|required|is_natural_no_zero',
            )
        );
        if (!empty($fields['balance'])) {
        	$config[] = array(
        		'field' => 'balance',
                'label' => '可用余额',
                'rules' => 'trim|required|numeric',
        	);
        	$data['balance'] = $fields['balance'];
        	$data['amount'] += $fields['balance'];
        }
        if (!empty($fields['frozen'])) {
        	$config[] = array(
        		'field' => 'frozen',
                'label' => '总冻结',
                'rules' => 'trim|required|numeric',
        	);
        	$data['frozen']  = $fields['frozen'];
        	$data['amount'] += $fields['frozen'];
        }
        if (!empty($fields['await'])) {
        	$config[] = array(
        		'field' => 'await',
                'label' => '总待收',
                'rules' => 'trim|required|numeric',
        	);
        	$data['await']   = $fields['await'];
        	$data['amount'] += $fields['await'];
        }

        $this->form_validation->set_data($fields);
        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() === FALSE) {
            $error = array(
                'error' => 2,
                'msg'	=> join(',' , $this->form_validation->error_array())
            );
            echo json_encode($error);
            return;
        }

        $data['uid'] = (int) $fields['uid'];
        if ($this->user_account->createUserAccount($data)) {
        	$error = array(
	    		'error' => 1,
	    		'msg'	=> '执行成功'
	    	);
        } else {
        	$error = array(
	    		'error' => 0,
	    		'msg'	=> '执行失败'
	    	);
        }

		echo json_encode($error);
		return ;
    }
}
