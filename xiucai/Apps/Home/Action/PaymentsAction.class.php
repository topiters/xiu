<?php
 namespace Home\Action;;
/**
* zhifu文件
* ==============================================
* 版权所有 2015-2016 http://www.chunni168.com
* ----------------------------------------------
* 这不是一个自由软件，未经授权不许任何使用和传播。
* ==============================================
* @date: 2016-11-19
* @author: top_iter、lnrp
* @email:2504585798@qq.com
* @version:1.0
*/
class PaymentsAction extends BaseAction{
	
	/**
	 * 获取支付宝URL
	 */
    public function getAlipayURL(){
    	$this->isUserLogin();
    	$morders = D('Home/Orders');
		$USER = session('WST_USER');
		$obj["userId"] = (int)$USER['userId'];

		$data = $morders->checkOrderPay($obj);
    	if($data["status"]==1){
    		$m = D('Home/Payments');
    		$url =  $m->getAlipayUrl();
    		$data["url"] = $url;
    	}
		$this->ajaxReturn($data);
	}

	public function getWeixinURL(){
		$this->isUserLogin();
		$morders = D('Home/Orders');
		$USER = session('WST_USER');
		$obj["userId"] = (int)$USER['userId'];
		
		$data = $morders->checkOrderPay($obj);
		if($data["status"]==1){
			$m = D('Home/Payments');
			$orderId = (int)I("orderId");
			if($orderId>0){
				$pkey = $obj["userId"]."@".$orderId."@1";
			}else{
				$pkey = $obj["userId"]."@".session("order")."@1";
			}
			$data["url"] = U('Home/WxPay/createQrcode',array("pkey"=>base64_encode($pkey)));
		}
		$this->ajaxReturn($data);
	}
	
	/**
	 * 支付
	 */
	public function toPay(){
		$this->isUserLogin();
		$USER = session('WST_USER');
		$morders = D('Home/Orders');
		//支付方式
		$pm = D('Home/Payments');
		$payments = $pm->getList();
		$this->assign("payments",$payments["onlines"]);
		$obj["orderId"] = (int)I("orderId");
		$ogj['orderType'] = 1;
		$data = $morders->getPayOrders($obj);
		$orders = $data["orders"];
		$needPay = 0;
        foreach ($orders as $v) {
            $needPay += $v[0]['coursePrice'];
		}
//		dump($orders);die;
		$this->assign("orderId",$obj["orderId"]);
		$this->assign("orders",$orders);
		$this->assign("needPay",$needPay);
		$this->assign("orderCnt",count($orders));
		$this->display('default/payment/order_pay');
	}
	
	/**
	 * 支付结果同步回调
	 */
	public function response(){
		$request = $_GET;
		unset($request['_URL_']);
		$pay_res = D('Payments')->notify($request);
		if($pay_res['status']){
			header('Location:../../index.php?m=Home&c=Orders&a=queryByPage',false);
			//支付成功业务逻辑
		}else{
			$this->error('支付失败');
		}
	}
	
	/**
	 * 支付结果异步回调
	 */
	public function notify(){
		$pm = D('Home/Payments');
		$request = $_POST;
		$pay_res = $pm->notify($request);
		if($pay_res['status']){
			//商户订单号
			$obj = array();
			$obj["trade_no"] = $_POST['trade_no'];
			$obj["out_trade_no"] = $_POST['out_trade_no'];
			$obj["total_fee"] = $_POST['total_fee'];
			$extras = explode("@",$_POST['extra_common_param']);
			$obj["userId"] = $extras[0];
			$obj["order_type"] = $extras[1];
			$obj["payFrom"] = 1;
			//支付成功业务逻辑
			$payments = $pm->complatePay($obj);
			echo 'success';
		}else{
			echo 'fail';
		}
	}
};
?>