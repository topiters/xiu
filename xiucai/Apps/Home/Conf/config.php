<?php
return array(
	//'配置项'=>'配置值'
	
   'AUTH_KEY'=>'www.taxwang.cn@88888888',
	define('WEB_HOST', WSTDomain()),
	/*微信支付配置*/
	'WxPayConf'=>array(
		'NOTIFY_URL' =>  WEB_HOST.'/index.php?m=Home&c=WxPay&a=notify',
		'CURL_TIMEOUT' => 30
	)
);