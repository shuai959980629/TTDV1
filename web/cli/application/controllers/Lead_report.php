<?php
/**
 * CLI任务集合
 *
 * @package	CONTROLLER
 * @author	LEE
 * @copyright	Copyright (c) 2015 tt1_p2p (http://cli.tt1.com.cn)
 * @license	http://cli.tt1.com.cn
 * @link	http://cli.tt1.com.cn
 * @since	Version 2015-06-24
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Lead_report extends MY_Controller
{
    public function __construct()
    {
    	header("Content-type:charset=utf-8");
        parent::__construct();
        $this->output->enable_profiler(false);
    }
   public $phone = array(
       '13981906901',
       '13881990808',
       '18011417246',
       '15884683353',
       /*'18628017405',*/
	);
    /**
     * 发送短信
     *
     * @access public
     * @return boolean
     */
    public function index()
    {
		$start_time = strtotime('-1 day 16:00');
		$end_time = 0;
		if (date('H') <= '11:10') {
            if (date('w') == 1 && (time() >= 1443628800 && time() < 1444492800) ) {
				$start_time = strtotime('last Friday 16:00');
			}
			$end_time   = strtotime('today 11:01');
		}
		else {
			$start_time = strtotime('today 11:01:01');
			$end_time   = strtotime('today 16:01');
		}
		$this->load->model('Cash_order_model', 'cash_order');
		$report = $this->cash_order->sum(array('money'),array('start_time'=>date("Y-m-d H:i:s",$start_time),'end_time'=>date("Y-m-d H:i:s",$end_time),'status'=>'pending'));
		$report['money'] = (!empty($report['money']))?$report['money']:0;

		$content = date('m月d日 H:i', $start_time) . ' 至 ' . date('m月d日 H:i', $end_time) . " 提现统计：";
		$content .= "￥{$report['money']}元";
		$content .= '【图腾贷】';

		$phone_arr = $this->phone;
		foreach($phone_arr as $ph){
			send_sms($ph,array('template'=>'cashreport_template','start_time'=>date('m月d日H:i', $start_time),'end_time'=>date('m月d日H:i', $end_time),'money'=>$report['money']));
		}

		$msg = '-----------------' . date('Y-m-d H:i:s') . '提现统计-----------------';
		$msg .= "\n{$content}";
    	//记录日志
    	log_message('error', $msg, 'cash_report');
    	return true;
    }
}
