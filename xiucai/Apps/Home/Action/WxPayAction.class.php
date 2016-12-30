<?php

namespace Home\Action;

use Think\Controller;

/**
 * 微信支付
 */
class WxPayAction extends BaseAction {
	/**
	 * 初始化
	 */
	private $wxpayConfig;
	private $wxpay;
	public function _initialize() {
		header ( "Content-type: text/html; charset=utf-8" );
		vendor ( 'WxPay.WxPayConf' );
		vendor ( 'WxPay.WxQrcodePay' );

		$this->wxpayConfig = C ( 'WxPayConf' );
		
		//var_dump($this->wxpayConfig);//array(2) { ["NOTIFY_URL"]=> string(55) "http://tax.hntax168.cn/Wstapi/payment/notify_weixin.php" ["CURL_TIMEOUT"]=> int(30) } 
		//exit;
		$m = D ( 'Home/Payments' );
		$this->wxpay = $m->getPayment ( "weixin" );
		$this->wxpayConfig ['appid'] = $this->wxpay ['appId']; // 微信公众号身份的唯一标识
		$this->wxpayConfig ['appsecret'] = $this->wxpay ['appsecret']; // JSAPI接口中获取openid
		$this->wxpayConfig ['mchid'] = $this->wxpay ['mchId']; // 受理商ID
		$this->wxpayConfig ['key'] = $this->wxpay ['apiKey']; // 商户支付密钥Key
		$this->wxpayConfig ['notifyurl'] = $this->wxpayConfig ['NOTIFY_URL'];
		$this->wxpayConfig ['returnurl'] = "";
		// 初始化WxPayConf_pub
		
		//var_dump($this->wxpayConfig );exit;
		$wxpaypubconfig = new \WxPayConf ( $this->wxpayConfig );
		//var_dump($wxpaypubconfig::$APPID);
		//exit;
	}
	
	public function createQrcode() {
		
		
		
		$pkey = base64_decode ( I ( "pkey" ) );
		$pkeys = explode ( "@", $pkey );
       //	dump($pkeys);   //array(3) {  [0] => string(2) "42"   UserId [1] => string(2) "68"  orderId  [2] => string(1) "1" Type}
      // exit;
		$pflag = true;
		if (count ( $pkeys ) != 3) {
			$this->assign ( 'out_trade_no', "" );
		} else {
			$morders = D ( 'Home/Orders' );
			$orderIds = $pkeys [1];//  [1] => string(8) "70,71,72" [2] => string(1) "1"
			$obj ["orderType"] = $pkeys [2];
			$needPay='';//支付金额
			$orderNum='';//订单号
			$orderCount=0;//订单数量
			if (strpos($orderIds,',')){//多个订单号
				//var_dump($orderIds);//in(".$ids.")
				$Sqla="select oo.*,cc.*  from __PREFIX__orders  oo left join   __PREFIX__order_course  cc ON  cc.orderId=oo.orderId  where  oo.orderId in (".$orderIds.")";
					
				$result=$morders->query($Sqla);
				
				//var_dump($result);
				//var_dump($morders->getLastSql());
				
				foreach ($result  as $k=>$v){
					$orderCount++;
					//var_dump($v);
					//exit;
					if($v['orderStatus']=='-2'){
						//var_dump($v['orderStatus']);
						//exit;
						$orderNum.=$v['orderNo'].',';
						$needPay+=$v['totalMoney'];
					
					}
			
			
				}
					
			}else{//单个订单号
				
				//$orderIds
				$Sqla="select  oo.*,cc.*  from __PREFIX__orders  oo left join   __PREFIX__order_course  cc ON  cc.orderId=oo.orderId  where  oo.orderId=$orderIds";
				$result=$morders->query($Sqla);//二维数组
				//var_dump($morders->getLastSql());
				//var_dump($result);
				//exit;
				if($result[0]['orderStatus']=='-2'){
					$needPay=$result[0]['totalMoney'];
					$orderNum=$result[0]['orderNo'];
					$orderCount=1;//订单数量
				}
					
			}
			
			//

			if($needPay>0){
				//var_dump($result);var_dump($orderNum);exit;
			    $this->assign ( "orders", $result );
				$this->assign ( "needPay", $needPay );
				//$this->assign ( "orderCnt", count ( $orders ) );
				
				// 使用统一支付接口
				$wxQrcodePay = new \WxQrcodePay ();
				$wxQrcodePay->setParameter ( "body", "支付订单費用" ); // 商品描述 
				$timeStamp = time ();
				$out_trade_no = "$timeStamp";
				//$out_trade_no = "1000001|1000002";
				$wxQrcodePay->setParameter ( "out_trade_no", "$out_trade_no" ); // 商户订单号
				$wxQrcodePay->setParameter ( "total_fee", $needPay * 100 ); // 总金额
				$wxQrcodePay->setParameter ( "notify_url", C ( 'WxPayConf.NOTIFY_URL' ) ); // 通知地址
				$wxQrcodePay->setParameter ( "trade_type", "NATIVE" ); // 交易类型
				$wxQrcodePay->setParameter ( "attach", "$orderNum" ); // 附加数据
				//$wxQrcodePay->setParameter ( "detail", "" );//附加数据
				$wxQrcodePay->SetParameter ( "input_charset", "UTF-8" );
				// 获取统一支付接口结果
				$wxQrcodePayResult = $wxQrcodePay->getResult ();
                // dump($wxQrcodePayResult);die;
				// 商户根据实际情况设置相应的处理流程
				if ($wxQrcodePayResult ["return_code"] == "FAIL") {
					// 商户自行增加处理流程
					echo "通信出错：" . $wxQrcodePayResult ['return_msg'] . "<br>";
				} elseif ($wxQrcodePayResult ["result_code"] == "FAIL") {
					// 商户自行增加处理流程
					echo "错误代码：" . $wxQrcodePayResult ['err_code'] . "<br>";
					echo "错误代码描述：" . $wxQrcodePayResult ['err_code_des'] . "<br>";
				} elseif ($wxQrcodePayResult ["code_url"] != NULL) {
					// 从统一支付接口获取到code_url
					$code_url = $wxQrcodePayResult ["code_url"];
					// 商户自行增加处理流程
				}
				
				$this->assign ( 'orderCount', $orderCount );
				$this->assign ( 'out_trade_no', $out_trade_no );
				$this->assign ( 'code_url', $code_url );
				$this->assign ( 'wxQrcodePayResult', $wxQrcodePayResult );
				
				session('order',NULL);
				//session_destroy();
				$this->display ( "default/payment/wxpay/qrcode" );
			}
		}
		
		
	}
	
	public function notify() {
		// 使用通用通知接口
		$wxQrcodePay = new \WxQrcodePay ();
		// 存储微信的回调
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$wxQrcodePay->saveData ( $xml );
		// 验证签名，并回应微信。
		if ($wxQrcodePay->checkSign () == FALSE) {
			$wxQrcodePay->setReturnParameter ( "return_code", "FAIL" ); // 返回状态码
			$wxQrcodePay->setReturnParameter ( "return_msg", "签名失败" ); // 返回信息
		} else {
			$wxQrcodePay->setReturnParameter ( "return_code", "SUCCESS" ); // 设置返回码
		}
		$returnXml = $wxQrcodePay->returnXml ();
		// ==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		if ($wxQrcodePay->checkSign () == TRUE) {
			if ($wxQrcodePay->data ["return_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} elseif ($wxQrcodePay->data ["result_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} else {
				// 此处应该更新一下订单状态，商户自行增删操作
				$order = $wxQrcodePay->getData ();
				
				if($order ["attach"]=='100'){
				$trade_no = $order["transaction_id"];	
				$total_fee = $order ["total_fee"];	
					
				$pkeys = explode ( "@", $order['detail'] );	
				 $userId=$pkeys[0];
				$courseId=$pkeys[1];	
				$payCourse=D('course')->where(array('courseId'=>$courseId))->find();
				if(
				$payCourse
				){
				$datav['orderNo']=$trade_no;//订单号
                $datav['shopId']=$payCourse['shopId'];
				$datav['totalMoney']=$total_fee;
				$datav['orderStatus']=2;
				$datav['userId']=$userId;
				$datav['payType']=1;
				$datav['createTime']=date('Y-m-d H:i:s');
				
				 $orderLiveId=D('orders')->add($datav);
                 if($orderLiveId){
				   $datam['orderId']= $orderLiveId;
				   $datam['courseId']= $courseId;
				   $datam['courseNums']= 1;
				   $datam['coursePrice']=$total_fee;
				  $datam['courseName']=$payCourse['courseName'];
				  $datam['courseThums']=$payCourse['courseThums'];
				   D('order_course')->add($datam);	
					
					}
					
				}
				
				
				
				
				
				
				
				
				
				
				}
				
				
				$trade_no = $order["transaction_id"];
				$total_fee = $order ["total_fee"];
				$pkey = $order ["attach"] ;
				$pkeys = explode ( "@", $pkey );
				$userId = $pkeys [0];
				$out_trade_no = $pkeys [1];
				$orderType = $pkeys [2];
				$pm = D ( 'Home/Payments' );
				// 商户订单号
				$obj = array ();
				$obj ["trade_no"] = $trade_no;
				$obj ["out_trade_no"] = $out_trade_no;
				$obj ["order_type"] = $orderType;
				$obj ["total_fee"] = $total_fee;
				$obj ["userId"] = $userId;
				$obj["payFrom"] = 2;
				// 支付成功业务逻辑
				$payments = $pm->complatePay ( $obj );
				S ("$out_trade_no",$total_fee);
			
				
				
				echo "SUCCESS";
			}
		}
	}
	
	/**
	 * 检查支付结果
	 */
	public function getPayStatus() {
		$trade_no = I ( 'trade_no' );
		$total_fee = S ( $trade_no );
		$data = array("status"=>-1);
		if(empty ( $total_fee )){
			$data["status"] = -1;
		}else{// 检查缓存是否存在，存在说明支付成功
			S ( $trade_no, null );
			$data["status"] = 1;
		}		
		$this->ajaxReturn($data);
	}
	
	/**
	 * 检查支付结果
	 */
	public function paySuccess() {
		
	session('order',NULL);
	//session_destroy();
		$this->display ( "default/payment/pay_success" );
	}
}