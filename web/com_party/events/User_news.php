<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_News
{
    public function work($data)
    {
        $CI = & get_instance();
        $CI->load->model('user_news_model', 'user_news');
        return $CI->user_news->saveData($data);
    }
}
