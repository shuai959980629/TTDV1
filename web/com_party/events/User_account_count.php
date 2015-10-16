<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_Account_Count
{
    public function work($data=array())
    {
        $CI = & get_instance();
        $CI->load->model('user_account_count_model', 'user_account_count');
    	return $CI->user_account_count->saveCount();
    }
}
