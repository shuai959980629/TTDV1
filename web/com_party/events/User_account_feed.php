<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_Account_Feed
{
    public function work($data)
    {
        $CI = & get_instance();
        $CI->load->model('user_account_log_model', 'user_account_log');
        return $CI->user_account_log->write($data);
    }
}
