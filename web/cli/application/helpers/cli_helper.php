<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 * @package		Libraries
 * @author		Glen.luo
 * @since		Version 1.0
 */

// ------------------------------------------------------------------------

/**
 * 写入管理员操作日志.
 *
 * @access	public
 * @return	null
 */
if ( ! function_exists('admin_log'))
{
	function admin_log($msg)
    {
        $CI = & get_instance();
        $data = array(
//            'uid'       => $_SESSION['user']['uid'],
            'action'    => $msg,
//            'ip'        => ip2long($CI->input->ip_address()),
//            'model'     => $CI->router->class,
        );
        $CI->load->model('admin_log_model', 'admin_log');
        $CI->admin_log->write($data);
	}
}

/**
 * 生成文件
 *
 * @param mixed	 $content	//存储内容 可以是数组、字符串、带<?php ?>等内容
 * @param string $file		//文件名称
 * @param string $logDir	//存放路径 默认 data/
 * @author LEE
 * @return void
 */
if ( ! function_exists('writeFile'))
{
	function writeFile($content, $file = 'log.txt', $logDir = '')
	{
		$logDir = !empty($logDir) ? $logDir : FCPATH . 'data/';
		if (!file_exists($logDir)) {
			@mkdir($logDir);
			@chmod($logDir, 0777);
		}
		
		if (is_array($content)) {
			$content = var_export($content, true);
		}
		
		$fp = fopen($logDir . '/' . $file, "w");
	    fwrite($fp, $content);
	    fclose($fp);
	}
}