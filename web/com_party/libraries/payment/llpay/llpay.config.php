<?php
//秘钥格式注意不能修改（左对齐，右边有回车符）
$llpay_config['RSA_PRIVATE_KEY'] ='-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCmRl6Zn4MmtoBoelHRT6j6ounts/x1+GiJTB9/eBTl01cBK50h
mOUtGBcOVrJCa0C1NkR8BYgOT/WLfFT8cICw6XSJtf2uzZco71jbwXfFe8MiEx/L
XiQNQHuclpkUa1hXFUUo6Qat8X8L++pVZfjav40dPKf7oFWCYLWBCDOdyQIDAQAB
AoGANe0mqz4/o+OWu8vIE1F5pWgG5G/2VjBtfvHwWUARzwP++MMzX/0dfsWMXLsj
b0UnpF3oUizdFn86TLXTPlgidDg6h0RbGwMZou/OIcwWRzgMaCVePT/D1cuhyD7Y
V8YkjVHGnErfxyia1COswAqcpiS4lcTG/RqkAMsdwSZe640CQQDRvkQ7M2WJdydc
9QLQ9FoIMnKx9mDge7+aN6ijs9gEOgh1gKUjenLr6hcGlLRyvYDKQ4b1kes22FUT
/n+AMaEPAkEAyvH05KRzax3NNdRPI45N1KuT1kydIwL3KpOK6mWuHlffed2EiWLS
dhZNiZy9wWuwFPqkrZ8g+jL0iKcCD0mjpwJBAKbWxWmeCZ+eY3ZjAtl59X/duTRs
ekU2yoN+0KtfLG64RvBI45NkHLQiIiy+7wbyTNcXfewrJUIcNRjRcVRkpesCQEM8
BbX6BYLnTKUYwV82NfLPJRtKJoUC5n/kgZFGPnkvA4qMKOybIL6ehPGiS/tYge1x
XD1pCrPZTco4CiambuECQDNtlC31iqzSKmgSWmA5kErqVJB0f1i+a0CbQLlaPGYN
/qwa7TE13yByaUdDDaTIEUrDyuqWd5+IvlbwuVsSlMw=
-----END RSA PRIVATE KEY-----';	

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//版本号
$llpay_config['version'] = '1.0';

//防钓鱼ip 可不传或者传下滑线格式 
$llpay_config['userreq_ip'] = '';

//证件类型
$llpay_config['id_type'] = '0';

//签名方式 不需修改
$llpay_config['sign_type'] = strtoupper('MD5');

//订单有效时间  分钟为单位，默认为10080分钟（7天） 
$llpay_config['valid_order'] ="10080";

//字符编码格式 目前支持 gbk 或 utf-8
$llpay_config['input_charset'] = strtolower('utf-8');

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$llpay_config['transport'] = 'https';
?>