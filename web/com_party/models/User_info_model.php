<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @用户详情模块
 * @author wangchuan
 * @category 2015-5-12
 * @version 
 */
class User_info_model extends Base_model{
	public $table;
	public $pk = 'uid';
    public function __construct()
    {
        parent::__construct();
        $this->table = 'user_info';
    }
	//返回用户详情个人资料字段
	public function get_user_info_fields(){
		return array(
			'qq'=>'QQ号码',
			'birthday'=>'出生年月',
			'sex'=>'性别',
			'marital'=>'婚姻状况',
			'school'=>'最高学历',
			'job'=>'职业',
			'city'=>'工作城市',
			'income'=>'月收入',
			'iscar'=>'是否购车',
			'address'=>'收件地址',
		);
	}
	

}
