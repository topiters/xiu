<?php
namespace Home\Action;
/**
 * ============================================================================
 * 直播控制器
 * 联系QQ:1149100178
 * ============================================================================
 * 会员控制器
 */
class LivecastAction extends BaseAction {
    /**
     * 跳去直播界面
     */
	 public function _initialize() {
		header ( "Content-type: text/html; charset=utf-8" );
		vendor ( 'WxPay.WxPayConf' );
		vendor ( 'WxPay.WxQrcodePay' );

		$wxpayConfig = C ( 'WxPayConf' );
		//var_dump($this->wxpayConfig);
		//var_dump($this->wxpayConfig);//array(2) { ["NOTIFY_URL"]=> string(55) "http://tax.hntax168.cn/Wstapi/payment/notify_weixin.php" ["CURL_TIMEOUT"]=> int(30) } 
		//exit;
		$m = D ( 'Payments' )->where(array('payCode'=>'weixin'))->find();
		//
		//exit;
		
		$wxpay = Json_decode($m['payConfig'],true);
		//var_dump($this->wxpay['appId']);
		//exit;
		$wxpayConfig ['appid'] = $wxpay ['appId']; // 微信公众号身份的唯一标识
		//var_dump($wxpayConfig ['appid']);exit;
		$wxpayConfig ['appsecret'] = $wxpay ['appsecret']; // JSAPI接口中获取openid
		$wxpayConfig ['mchid'] =$wxpay['mchId']; // 受理商ID
		$wxpayConfig ['key'] = $wxpay ['apiKey']; // 商户支付密钥Key
		$wxpayConfig ['notifyurl'] = $wxpayConfig ['NOTIFY_URL'];
		//$this->wxpayConfig ['returnurl'] = "/index.php?m=Home&c=WxPay&a=paySuccess";
		// 初始化WxPayConf_pub
		
		//var_dump($this->wxpayConfig );exit;
		$wxpaypubconfig = new \WxPayConf ($wxpayConfig );
		//var_dump($wxpaypubconfig::$APPID);
	//exit;
	}
	 
	 
	 
	 
	 public function index(){
         $page = D('Live')->getList();
         $pager = new \Think\Page($page['total'] , $page['pageSize'] , I());// 实例化分页类 传入总记录数和每页显示的记录数
         $page['pager'] = $pager->show();
         $this->assign('page' , $page);
//         dump($page);die;
		 $this->display('default/livecast_index');
		 
	 }

    /**
     * 直播课程详情
     */
    public function course() {
        if (I('id')){
            //课程详情
            $id = I('id');
            $course = D('course')->where("courseId = $id")->find();
            $catName = D('course_cats')->field('catName')->where("catId = {$course['courseCatId3']}")->find();
            $course['catName'] = $catName['catName'];
            $course['liveStartTime'] = strtotime($course['liveStartTime']);
            $course['liveEndTime'] = strtotime($course['liveEndTime']);
//            dump($course);die;
            $this->assign('course',$course);
            //导师详情
            $tutor = D('shops')->field('shopId,shopName,shopImg,shopDetails')->where("shopId = {$course['shopId']}")->find();
            $this->assign('tutor',$tutor);
//            dump($tutor);dump($course);die;
            //相关课程
            $cid = $course['courseCatId2'];
            $related = D('course')->where("courseCatId2 = $cid and courseCatId3 <> {$course['courseCatId3']}")->limit(4)->select();
            $this->assign('related',$related);
//            dump($related);die;
            //判断登录用户是否已经报名该课程
            $user = session('WST_USER');
            $re = D('course_record')->where("uid = {$user['userId']} and cid =".I('id'))->find();
            if ($re) {
                $this->assign('sign' , 2);
                $this->assign('sign_id' ,$re['id']);
            } else {
                $this->assign('sign' , 1);
            }
			
			
			$uid=$user['userId'];
			$cid=$course['courseId'];
			// 使用统一支付接口
				$wxQrcodePay = new \WxQrcodePay ();
				$wxQrcodePay->setParameter ( "body", "直播课程费用" ); // 商品描述 
				$timeStamp = time ();
				$out_trade_no =$timeStamp;
				//$out_trade_no = "1000001|1000002";
				$wxQrcodePay->setParameter ( "out_trade_no", "$out_trade_no" ); // 商户订单号
				$wxQrcodePay->setParameter ( "total_fee", $course['shopPrice'] * 100 ); // 总金额
				$wxQrcodePay->setParameter ( "notify_url", C ( 'WxPayConf.NOTIFY_URL' ) ); // 通知地址
				$wxQrcodePay->setParameter ( "trade_type", "NATIVE" ); // 交易类型
				$wxQrcodePay->setParameter ( "attach", "100aa@$uid@$cid" ); // 附加数据
				//$wxQrcodePay->setParameter ( "detail","");//附加数据
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
				
				//$this->assign ( 'orderCount', $orderCount );
				$this->assign ( 'out_trade_no', $out_trade_no );
				$this->assign ( 'code_url', $code_url );
				$this->assign ( 'wxQrcodePayResult', $wxQrcodePayResult );
			

            $this->display('default/livecast_course');
        } else {
            redirect(U('Home/Livecast/index'));
        }
    }
	
	
	public function livePay(){
		
		
		
		
		
	}
	
	
	public function getPayStatus(){
		
		
		
	}
	
	
	
	
	

    /**
     * 报名课程
     */
    public function sign() {
    	$this->isUserLogin();
        if ($_POST) {
            $_POST['ctime'] = time();
            $re = D('course_record')->add($_POST);
            if ($re) {
                D('course')->query("update __PREFIX__course set saleCount = saleCount + 1 where courseId = {$_POST['cid']}");
                echo  $re ;
            }
        }
    }
    
    /**
     * 直播页面
     */
    public function live() {
  	$this->isUserLogin();
    	$user=session('WST_USER');
    	$cid=I('id');//报名id
    	$courseId=I('courseId');//课程id
    	$uid = $user['userId'];
    	 $re = D('course_record')->where("uid = {$user['userId']} and id =".I('id'))->find();
    	if(!$re){
    		
    		$this->error('没有报名该直播。。。');
    		
    	} 
    	
    	
    	$liveOne=D('course')->where(array('courseId'=>$courseId))->find();
    	if($liveOne){
    	$this->assign('lid',$liveOne['vid']);
    	} 
    
    	
    	$this->display('default/living');
       
    }
}