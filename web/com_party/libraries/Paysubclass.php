<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 支付接口规范
 */
interface Paysubclass{

	public function PaySubmit($data=array());

	public function Callback();
}