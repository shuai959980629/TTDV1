<?php
    class MY_Controller extends CI_Controller
    {
        public $theme          = '';
        public $lay            = 'layout';
        public $batch_action   = array();

        public function __construct()
        {
            parent::__construct();

            $this->output->enable_profiler(FALSE);

            Event::loadCfg();
        }

        protected function render($data = array(), $view = '', $content_type = 'auto', $return = FALSE)
        {
	        if ($content_type == 'auto') {
                $content_type = $this->input->is_ajax_request() ? 'json' : 'html';
            }

            switch ($content_type) {
                case 'json':
                    if ($return === FALSE) {
                        $this->output->enable_profiler(FALSE);
                        $this->output
                                ->set_status_header(200)
                                ->set_content_type('application/json', 'utf-8')
                                ->set_output(json_encode($data));
                    }
                    else {
                        return json_encode($data);
                    }
                    break;
                case 'html':
                default:
                    $data['batch_action']   = $this->batch_action;
                    if (empty($view)) {
                        $view = $this->router->class . '/' . $this->router->method . '.php';
                    }
                    return $this->layout->load($data, $this->lay, $view, $this->theme, $return);
                    break;
            }
        }


        /**
         * 返回客户端信息通用函数
         * @param number $status 返回状态
         * @param string $data	包含的数据
         * @param string $msg	状态说明
         */
        protected function return_client($status = 0, $data = null, $msg = null)
        {
            $requesttype = $this->input->is_ajax_request();
            if(ENVIRONMENT !== 'production' || ($requesttype && strtolower($_SERVER['REQUEST_METHOD']) == 'post')){
                header('Content-type: application/json;charset=utf-8');
                $resp = array(
                    'status' => $status,
                    'data' => empty($data) ? null : $data,
                    'msg' => empty($msg) ? null : $msg,
                    'time' => date('Y-m-d H:i:s', time()));//microtime(true) - $starttime);
                $json = json_encode($resp);
                die($json);
            }
        }

        public function batch()
        {
            $action = $this->input->post('batch_action');
            log_message('debug', "batch action: {$action}");
            if (!empty($action)) {
                $this->{$action}();
            }
            else {
                $url = "/{$this->router->class}/index";
                $this->_error_handler(
                    array('fail' => '请选择需要批量执行的操作'), $url
                );
            }
        }

    }
