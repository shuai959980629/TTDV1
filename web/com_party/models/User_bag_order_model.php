<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @用户道具套餐订单
 * @author houxijian
 * @category 2015-10-9
 * @version 
 */
class User_bag_order_model extends Base_model{
	public $table;
	public $pk = 'uid';
    public function __construct()
    {
        parent::__construct();
        $this->table = 'user_bag_order';
    }
}
