<?php
return array(
	//'配置项'=>'配置值'
	
   'AUTH_KEY'=>'www.taxwang.cn@88888888',
	define('WEB_HOST', WSTDomain()),
	/*微信支付配置*/
	'WxPayConf'=>array(
		'NOTIFY_URL' =>  WEB_HOST.'/payment/notify_weixin.php',
		'CURL_TIMEOUT' => 60
	)
);