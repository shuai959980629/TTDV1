<?php
    class MY_Controller extends CI_Controller
    {
        public $theme          = '';
        public $lay            = 'layout';

        public function __construct()
        {
            parent::__construct();

            $this->output->enable_profiler(false);
            
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
                    if (empty($view)) {
                        $view = $this->router->class . '/' . $this->router->method . '.php';
                    }
                    return $this->layout->load($data, $this->lay, $view, $this->theme, $return);
                    break;
            }
        }
    }
