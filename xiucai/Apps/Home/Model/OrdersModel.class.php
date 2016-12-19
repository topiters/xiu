<?php
namespace Home\Model;
/**
* 订单 文件
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
class OrdersModel extends BaseModel {
	/**
	 * 获以订单列表
	 */
	public function getOrdersList($obj){
		$userId = $obj["userId"];
		$m = M('orders');
		$sql = "SELECT * FROM __PREFIX__orders WHERE orderFlag=1 and userId = $userId AND orderStatus <>-1 order by createTime desc";		
		return $m->pageQuery($sql);
	}
	
	/**
	 * 取消订单记录 
	 */
	public function getcancelOrderList($obj){		
		$userId = $obj["userId"];
		$m = M('orders');
		$sql = "SELECT * FROM __PREFIX__orders WHERE orderFlag=1 and userId = $userId AND orderStatus =-1 order by createTime desc";		
		return $m->pageQuery($sql);
		
	}

	/**
	 * 获取订单详情
	 */
	public function getOrdersDetails($obj){		
		$orderId = $obj["orderId"];
		$sql = "SELECT od.*,sp.shopName 
				FROM __PREFIX__orders od, __PREFIX__shops sp 
				WHERE orderFlag=1 and od.shopId = sp.shopId And orderId = $orderId ";		
		$rs = $this->query($sql);;	
		return $rs;
		
	}
	
	/**
	 * 获取订单课程信息
	 */
	public function getOrdersCourse($obj){	
			
		$orderId = $obj["orderId"];
		$sql = "SELECT g.*,og.courseNums as ocourseNums,og.coursePrice as ocoursePrice 
				FROM __PREFIX__order_course og, __PREFIX__course g 
				WHERE og.orderId = $orderId AND og.courseId = g.courseId ";		
		$rs = $this->query($sql);	
		return $rs;
		
	}
	
	/**
	 * 
	 * 获取订单课程详情
	 */
	public function getOrdersCourseDetails($obj){	
			
		$orderId = $obj["orderId"];
		$sql = "SELECT g.*,og.courseNums as ocourseNums,og.coursePrice as ocoursePrice ,ga.id as gaId
				FROM __PREFIX__order_course og, __PREFIX__course g 
				LEFT JOIN __PREFIX__course_appraises ga ON g.courseId = ga.courseId AND ga.orderId = $orderId
				WHERE og.orderId = $orderId AND og.courseId = g.courseId";		
		$rs = $this->query($sql);	
		return $rs;
		
	}
	
	/**
	 *
	 * 获取订单课程详情
	 */
	public function getPayOrders($obj){
//	    dump($obj["orderType"]);die;
		$orderType = (int)$obj["orderType"];
		$orderId = (int)$obj["orderId"];
		$orderunique = 0;
		if($orderType>0){//来在线支付接口
			$uniqueId = $obj["uniqueId"];
			if($orderType==1){
				$orderId = (int)$uniqueId;
			}else{
				$orderunique = WSTAddslashes($uniqueId);
			}
		}else{
			$orderId = (int)$obj["orderId"];
			$orderunique = session("WST_ORDER_UNIQUE");
		}
		
		if($orderId>0){
			$sql = "SELECT o.orderId, o.orderNo, g.courseId, g.courseName ,og.courseAttrName , og.courseNums ,og.coursePrice
				FROM __PREFIX__order_course og, __PREFIX__course g, __PREFIX__orders o
				WHERE o.orderId = og.orderId AND og.courseId = g.courseId AND o.payType=1 AND orderFlag =1 AND o.isPay=0 AND o.orderStatus = -2 AND o.orderId =$orderId";
            $rslist = $this->query($sql);
		}else{
            $ids = session('order');
            if (is_array($ids)){
                $ids = explode('/' , $ids);
                foreach ($ids as $vid) {
                    $sql = "SELECT o.orderId, o.orderNo, g.courseId, g.courseName ,og.courseAttrName , og.courseNums ,og.coursePrice
				FROM __PREFIX__order_course og, __PREFIX__course g, __PREFIX__orders o
				WHERE o.orderId = og.orderId AND og.courseId = g.courseId AND o.payType=1 AND orderFlag =1 AND o.isPay=0 AND o.orderStatus = -2 AND o.orderId =$vid";
                    $arr = $this->query($sql);
                    $rslist[] = $arr[0];
//                dump($rslist);die;
                }
            } else {
                $sql = "SELECT o.orderId, o.orderNo, g.courseId, g.courseName ,og.courseAttrName , og.courseNums ,og.coursePrice
				FROM __PREFIX__order_course og, __PREFIX__course g, __PREFIX__orders o
				WHERE o.orderId = og.orderId AND og.courseId = g.courseId AND o.payType=1 AND orderFlag =1 AND o.isPay=0 AND o.orderStatus = -2 AND o.orderId =$ids";
                $rslist = $this->query($sql);
            }
		}
//        dump($rslist);die;
		$orders = array();
		foreach ($rslist as $key => $order) {
			$orders[$order["orderNo"]][] = $order;
		}
		if($orderId>0){
			$sql = "SELECT SUM(needPay) needPay FROM __PREFIX__orders WHERE orderId = $orderId AND isPay=0 AND payType=1 AND needPay>0 AND orderStatus = -2 AND orderFlag =1";
		}else{
			$sql = "SELECT SUM(needPay) needPay FROM __PREFIX__orders WHERE orderunique = '$orderunique' AND isPay=0 AND payType=1 AND needPay>0 AND orderStatus = -2 AND orderFlag =1";
		}
		$payInfo = self::queryRow($sql);
		$data["orders"] = $orders;
		$data["needPay"] = $payInfo["needPay"];
		return $data;
	
	}
	
	/**
	 * 下单
	 */
	public function submitOrder(){
		$rd = array('status'=>-1);
		$USER = session('WST_USER');
		$coursemodel = D('Home/Course');
		$morders = D('Home/Orders');
		$totalMoney = 0;
		$totalCnt = 0;
		$userId = (int)session('WST_USER.userId');
		
		/* $consigneeId = (int)I("consigneeId");
		$payway = (int)I("payway");
		$isself = (int)I("isself");
		$needreceipt = (int)I("needreceipt"); */
		$consigneeId = 222;
		$payway = 1;
		$isself = 2;
		$needreceipt ="感谢你下单";
		
		
		$orderunique = WSTGetMillisecond().$userId;

		
		$sql = "select count(cartId) cnt from __PREFIX__cart where userId = $userId and isCheck=1 and courseCnt>0";
		$rcnt = $this->queryRow($sql);
		
		$cartcourse = array();	
		$order = array();
		if($rcnt['cnt']==0){
			$rd['msg'] = '购物车为空!';
			return $rd;
		}else{
			
			$sql = "select * from __PREFIX__cart where userId = $userId and packageId>0 group by batchNo";
			$shopcart = $this->query($sql);
			$batchNos = array();
			for($i=0;$i<count($shopcart);$i++){
				$ccourse = $shopcart[$i];
				$package = array();
				$batchNo = $ccourse["batchNo"];
				$package["batchNo"] = $batchNo;
				$batchNos[] = $batchNo;
				$pkgShopPrice = 0;
				$pckMinStock = 0;
				$sql = "select * from __PREFIX__cart where userId = $userId and batchNo=$batchNo";
				$pkgList = $this->query($sql);
				for($j=0;$j<count($pkgList);$j++){
					$pcourse = $pkgList[$j];
					$packageId = $pcourse["packageId"];
					$courseId = (int)$pcourse["courseId"];
					$package["packageId"] = $packageId;
					$package["courseCnt"] = (int)$pcourse["courseCnt"];
			
					$sql = "select p.shopId, p.packageName, gp.diffPrice from __PREFIX__course_packages gp, __PREFIX__packages p where p.packageId =$packageId and gp.packageId=p.packageId and gp.courseId = $courseId";
					$pkg = $this->queryRow($sql);
			
					$diffPrice = (float)$pkg["diffPrice"];
					if($pkg["shopId"]>0){
						$package["packageName"] = $pkg["packageName"];
						$package["shopId"] = $pkg["shopId"];
					}
					$courseAttrId = (int)$pcourse["courseAttrId"];
					$course = $coursemodel->getCourseSimpInfo($courseId,$courseAttrId);
					
					//核对课程是否符合购买要求
					if(empty($course)){
						$rd['msg'] = '找不到指定的课程aa!';
						return $rd;
					}
					if($course['courseStock']<$package["courseCnt"]){
						$rd['msg'] = '对不起，课程'.$course['courseName'].'库存不足!';
						return $rd;
					}
					if($course['isSale']!=1){
						$rd['msg'] = '对不起，课程库'.$course['courseName'].'已下架!';
						return $rd;
					}
			
					$course['oshopPrice'] = $course['shopPrice'];
					$course['shopPrice'] = ($course['shopPrice']>$diffPrice)?($course['shopPrice']-$diffPrice):$course['shopPrice'];
					$pckMinStock = ($pckMinStock==0 || $course['courseStock']<$pckMinStock)?$course['courseStock']:$pckMinStock;
					$pkgShopPrice += $course['shopPrice'];
			
			
					$course["cnt"] = $pcourse["courseCnt"];
					$course["ischk"] = $pcourse["isCheck"];
					$totalMoney += $course["cnt"]*$course["shopPrice"];
					$cartcourse[$course["shopId"]]["ischk"] = 1;
					$package["course"][] = $course;
			
					$cartcourse[$course["shopId"]]["deliveryFreeMoney"] = $course["deliveryFreeMoney"];//店铺免运费最低金额
					$cartcourse[$course["shopId"]]["deliveryMoney"] = $course["deliveryMoney"];//店铺配送费
					$cartcourse[$course["shopId"]]["totalCnt"] = $cartcourse[$course["shopId"]]["totalCnt"]+$ccourse["courseCnt"];
					$cartcourse[$course["shopId"]]["totalMoney"] = $cartcourse[$course["shopId"]]["totalMoney"]+($course["cnt"]*$course["shopPrice"]);
				}

				$package["courseStock"] = $pckMinStock;
				$package["shopPrice"] = $pkgShopPrice;
				$cartcourse[$course["shopId"]]["packages"][] = $package;
			}
			
			$sql = "select * from __PREFIX__cart where userId = $userId and isCheck=1 and courseCnt>0 and packageId=0";
			$shopcart = $this->query($sql);
			//整理及核对购物车数据
			$cartIds = array();
			for($i=0;$i<count($shopcart);$i++){
				$ccourse = $shopcart[$i];
				$courseId = (int)$ccourse["courseId"];
				$courseAttrId = (int)$ccourse["courseAttrId"];
				
				$course = $coursemodel->getCourseSimpInfo($courseId,$courseAttrId);
				//核对课程是否符合购买要求
				if(empty($course)){
					$rd['msg'] = '找不到指定的课程v!';
					return $rd;
				}
				if($course['courseStock']<=0){
					$rd['msg'] = '对不起，课程'.$course['courseName'].'库存不足!';
					return $rd;
				}
				if($course['isSale']!=1){
					$rd['msg'] = '对不起，课程库'.$course['courseName'].'已下架!';
					return $rd;
				}
				$course["cnt"] = $ccourse["courseCnt"];
				$cartcourse[$course["shopId"]]["shopcourse"][] = $course;
				$cartcourse[$course["shopId"]]["deliveryFreeMoney"] = $course["deliveryFreeMoney"];//店铺免运费最低金额
				$cartcourse[$course["shopId"]]["deliveryMoney"] = $course["deliveryMoney"];//店铺免运费最低金额
				$cartcourse[$course["shopId"]]["totalCnt"] = $cartcourse[$course["shopId"]]["totalCnt"]+$ccourse["courseCnt"];
				$cartcourse[$course["shopId"]]["totalMoney"] = $cartcourse[$course["shopId"]]["totalMoney"]+($course["cnt"]*$course["shopPrice"]);
				$cartIds[] = $ccourse["cartId"];
				
			}
			//var_dump($cartcourse);
		//	exit;
			$morders->startTrans();	
			try{
				$ordersInfo = $morders->addOrders($userId,$consigneeId,$payway,$needreceipt,$cartcourse,$orderunique,$isself);
				$morders->commit();	
				if(count($cartIds)>0){
					$sql = "delete from __PREFIX__cart where userId = $userId and cartId in (".implode(",",$cartIds).")";
					$this->execute($sql);
				}
				if(count($batchNos)>0){
					$sql = "delete from __PREFIX__cart where userId = $userId and batchNo in (".implode(",",$batchNos).")";
					$this->execute($sql);
				}
				$rd['orderIds'] = implode(",",$ordersInfo["orderIds"]);
				$rd['status'] = 1;
				session("WST_ORDER_UNIQUE",$orderunique);
			}catch(Exception $e){
				$morders->rollback();
				$rd['msg'] = '下单出错，请联系管理员!';
			}
			return $rd;
		}		
	}
	
	/**
	 * 生成订单
	 */
	public function addOrders($userId,$consigneeId,$payway,$needreceipt,$catcourse,$orderunique,$isself){	
		
		$orderInfos = array();
		$orderIds = array();
		$orderNos = array();
		//$remarks = I("remarks");
		
		//$addressInfo = UserAddressModel::getAddressDetails($consigneeId);
        
        
		foreach ($catcourse as $key=> $shopcourse){
			$m = M('orderids');
			//生成订单ID
			$orderSrcNo = $m->add(array('rnd'=>time()));
			$orderNo = $orderSrcNo."".(fmod($orderSrcNo,7));
			//创建订单信息
			$data = array();
			$packages = $shopcourse["packages"];
			$shopId = (int)$packages[0]["shopId"];
			$deliverType = intval($packages[0]["deliveryType"]);
			
			$pshopcourse = $shopcourse["shopcourse"];
			$shopId = ($shopId>0)?$shopId:($pshopcourse[0]["shopId"]);
			
			$data["orderNo"] = $orderNo;
			$data["shopId"] = $shopId;	
			$deliverType = ($deliverType>0)?$deliverType:intval($pshopcourse[0]["deliveryType"]);
			$data["userId"] = $userId;	
				
			$data["orderFlag"] = 1;
			$data["totalMoney"] = $shopcourse["totalMoney"];
			if($isself==1){//自提
				$deliverMoney = 0;
			}else{
				$deliverMoney = ($shopcourse["totalMoney"]<$shopcourse["deliveryFreeMoney"])?$shopcourse["deliveryMoney"]:0;
			}
			$data["deliverMoney"] = $deliverMoney;
			$data["payType"] = $payway;
			$data["deliverType"] = $deliverType;
			//$data["userName"] = $addressInfo["userName"];
			//$data["areaId1"] = $addressInfo["areaId1"];
		//	$data["areaId2"] = $addressInfo["areaId2"];
		//	$data["areaId3"] = $addressInfo["areaId3"];
		//	$data["communityId"] = $addressInfo["communityId"];
		//	$data["userAddress"] = $addressInfo["paddress"]." ".$addressInfo["address"];
		//	$data["userTel"] = $addressInfo["userTel"];
		//	$data["userPhone"] = $addressInfo["userPhone"];
			
			$data['orderScore'] = floor($data["totalMoney"]);
			$data["isInvoice"] = $needreceipt;		
			$data["orderRemarks"] = $remarks;
			$data["requireTime"] = I("requireTime");
			$data["invoiceClient"] = I("invoiceClient");
			$data["isAppraises"] = 0;
			$data["isSelf"] = $isself;
			
			$isScorePay = (int)I("isScorePay",0);
			$scoreMoney = 0;
			$useScore = 0;

			if($GLOBALS['CONFIG']['poundageRate']>0){
				$data["poundageRate"] = (float)$GLOBALS['CONFIG']['poundageRate'];
				$data["poundageMoney"] = WSTBCMoney($data["totalMoney"] * $data["poundageRate"] / 100,0,2);
			}else{
				$data["poundageRate"] = 0;
				$data["poundageMoney"] = 0;
			}
			if($GLOBALS['CONFIG']['isOpenScorePay']==1 && $isScorePay==1){//积分支付
				$baseScore = WSTOrderScore();
				$baseMoney = WSTScoreMoney();
				$sql = "select userId,userScore,LoginName from __PREFIX__users where userId=$userId";
				$user = $this->queryRow($sql);
				$loginName=$user['LoginName'];
				$useScore = $baseScore*floor($user["userScore"]/$baseScore);
				$scoreMoney = $baseMoney*floor($user["userScore"]/$baseScore);
				$orderTotalMoney = $shopcourse["totalMoney"]+$deliverMoney;
				if($orderTotalMoney<$scoreMoney){//订单金额小于积分金额
					$useScore = $baseScore*floor($orderTotalMoney/$baseMoney);
					$scoreMoney = $baseMoney*floor($orderTotalMoney/$baseMoney);
				}
				$data["useScore"] = $useScore;
				$data["scoreMoney"] = $scoreMoney;
			}
			$data["realTotalMoney"] = $shopcourse["totalMoney"]+$deliverMoney - $scoreMoney;
			$data["needPay"] = $shopcourse["totalMoney"]+$deliverMoney - $scoreMoney;
			
			$data["createTime"] = date("Y-m-d H:i:s");
			if($payway==1){
				$data["orderStatus"] = -2;
			}else{
				$data["orderStatus"] = 0;
			}
			
			$data["orderunique"] = $orderunique;
			$data["isPay"] = 0;
			if($data["needPay"]==0){
				$data["isPay"] = 1;
			}
			
			$morders = M('orders');
			$orderId = $morders->add($data);	

			//订单创建成功则建立相关记录
			if($orderId>0){
				
				if($GLOBALS['CONFIG']['isOpenScorePay']==1 && $isScorePay==1 && $useScore>0){//积分支付
					$sql = "UPDATE __PREFIX__users set userScore=userScore-".$useScore." WHERE userId=".$userId;
					$rs = $this->execute($sql);
				
					$data = array();
					$m = M('user_score');
					$data["userId"] = $userId;
					$data["score"] = $useScore;
					$data["dataSrc"] = 1;
					$data["dataId"] = $orderId;
					$data["dataRemarks"] = "订单支付-扣积分";
					$data["scoreType"] = 2;
					$data["createTime"] = date('Y-m-d H:i:s');
					$m->add($data);
				}
				
				$orderIds[] = $orderId;
				//建立订单课程记录表
				$mog = M('order_course');
				$sqlu = "select userId ,LoginName from __PREFIX__users where userId=$userId";
				$userm = $this->queryRow($sqlu);
				foreach ($packages as $key=> $package){
					foreach ($package['course'] as $key2=> $scourse){
						$data = array();
						$data["orderId"] = $orderId;
						$data["courseId"] = $scourse["courseId"];
						$data["courseAttrId"] = (int)$scourse["courseAttrId"];
						if($scourse["attrVal"]!='')$data["courseAttrName"] = $scourse["attrName"].":".$scourse["attrVal"];
						$data["courseNums"] = $scourse["cnt"];
						$data["coursePrice"] = $scourse["shopPrice"];
						$data["courseName"] = $scourse["courseName"];
						$data["courseThums"] = $scourse["courseThums"];
						$data["buyerName"]=$userm['LoginName'] ;
						$mog->add($data);
					}
				}
				
				foreach ($pshopcourse as $key=> $scourse){
					$data = array();
					$data["orderId"] = $orderId;
					$data["courseId"] = $scourse["courseId"];
					$data["courseAttrId"] = (int)$scourse["courseAttrId"];
					if($scourse["attrVal"]!='')$data["courseAttrName"] = $scourse["attrName"].":".$scourse["attrVal"];
					$data["courseNums"] = $scourse["cnt"];
					$data["coursePrice"] = $scourse["shopPrice"];
					$data["courseName"] = $scourse["courseName"];
					$data["courseThums"] = $scourse["courseThums"];
					$data["buyerName"]=$userm['LoginName'] ;
					$mog->add($data);
				}
			
				if($payway==0){
					//建立订单记录
					$data = array();
					$data["orderId"] = $orderId;
					$data["logContent"] = ($deliverType==0)? "下单成功":"下单成功等待审核";
					$data["logUserId"] = $userId;
					$data["logType"] = 0;
					$data["logTime"] = date('Y-m-d H:i:s');
					$mlogo = M('log_orders');
					$mlogo->add($data);
					//建立订单提醒
					$sql ="SELECT userId,shopId,shopName FROM __PREFIX__shops WHERE shopId=$shopId AND shopFlag=1  ";
					$users = $this->query($sql);
					$morm = M('order_reminds');
					for($i=0;$i<count($users);$i++){
						$data = array();
						$data["orderId"] = $orderId;
						$data["shopId"] = $shopId;
						$data["userId"] = $users[$i]["userId"];
						$data["userType"] = 0;
						$data["remindType"] = 0;
						$data["createTime"] = date("Y-m-d H:i:s");
						$morm->add($data);
					}
					
					//修改库存
					foreach ($packages as $key=> $package){
						foreach ($package['course'] as $key2=> $scourse){
							$sql="update __PREFIX__course set courseStock=courseStock-".$scourse['cnt']." where courseId=".$scourse["courseId"];
							$this->execute($sql);
							if((int)$scourse["courseAttrId"]>0){
								$sql="update __PREFIX__course_attributes set attrStock=attrStock-".$scourse['cnt']." where id=".$scourse["courseAttrId"];
								$this->execute($sql);
							}
						}
					}
					
					foreach ($pshopcourse as $key=> $scourse){
						$sql="update __PREFIX__course set courseStock=courseStock-".$scourse['cnt']." where courseId=".$scourse["courseId"];
						$this->execute($sql);
						if((int)$scourse["courseAttrId"]>0){
							$sql="update __PREFIX__course_attributes set attrStock=attrStock-".$scourse['cnt']." where id=".$scourse["courseAttrId"];
							$this->execute($sql);
						}
					}
					
				}else{
					$data = array();
					$data["orderId"] = $orderId;
					$data["logContent"] = "订单已提交，等待支付";
					$data["logUserId"] = $userId;
					$data["logType"] = 0;
					$data["logTime"] = date('Y-m-d H:i:s');
					$mlogo = M('log_orders');
					$mlogo->add($data);
				}
			}
		}
		
		return array("orderIds"=>$orderIds);
		
	}
	
	/**
	 * 获取订单参数
	 */
	public function getOrderListByIds(){
		 $orderunique = session("WST_ORDER_UNIQUE");
		 $orderInfos = array('totalMoney'=>0,'isMoreOrder'=>0,'list'=>array());
		 $sql = "select orderId,orderNo,totalMoney,deliverMoney,realTotalMoney
		         from __PREFIX__orders where userId=".(int)session('WST_USER.userId')." 
		         and orderunique='".$orderunique."' and orderFlag=1 ";
	     $rs = $this->query($sql);
	     if(!empty($rs)){
	     	$totalMoney = 0;
	     	$realTotalMoney = 0;
	     	foreach ($rs as $key =>$v){
	     		$orderInfos['list'][] = array('orderId'=>$v['orderId'],'orderNo'=>$v['orderNo']);
	     		$totalMoney += $v['totalMoney'] + $v['deliverMoney'];
	     		$realTotalMoney += $v['realTotalMoney'];
	     	}
	     	$orderInfos['totalMoney'] = $totalMoney;
	     	$orderInfos['realTotalMoney'] = $realTotalMoney;
	     	$orderInfos['isMoreOrder'] = (count($rs)>0)?1:0;
	     }
	     return $orderInfos;
	}
	
	/**
	 * 获取待付款订单
	 */
	public function queryByPage($obj){
		$userId = $obj["userId"];
		$pcurr = (int)I("pcurr",0);
		$sql = "SELECT o.* FROM __PREFIX__orders o
				WHERE userId = $userId AND orderFlag=1 order by orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);	
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
	        $sql = "SELECT og.courseId,og.courseName,og.coursePrice,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";	
	        $glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}

	/**
	 * 获取待付款订单
	 */
	public function queryPayByPage($obj){
		$userId = (int)$obj["userId"];
		$orderNo = WSTAddslashes(I("orderNo"));
		$orderStatus = (int)I("orderStatus",0);
		$courseName = WSTAddslashes(I("courseName"));
		$shopName = WSTAddslashes(I("shopName"));
		$userName = WSTAddslashes(I("userName"));
		$pcurr = (int)I("pcurr",0);
		
		$sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName 
		        FROM __PREFIX__orders o,__PREFIX__shops sp 
		        WHERE o.orderFlag=1 and o.userId = $userId AND o.orderStatus =-2 AND o.isPay = 0 AND o.payType = 1 AND o.shopId=sp.shopId ";
		if($orderNo!=""){
			$sql .= " AND o.orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND o.userName like '%$userName%'";
		}
		if($shopName!=""){
			$sql .= " AND sp.shopName like '%$shopName%'";
		}
		$sql .= " order by o.orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);	
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
	        $sql = "SELECT og.courseId,og.courseName,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";	
			$glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
	
	
	
	/**
	 * 获取待确认收货
	 */
	public function queryReceiveByPage($obj){
		$userId = (int)$obj["userId"];
		$orderNo = WSTAddslashes(I("orderNo"));
		$orderStatus = (int)I("orderStatus",0);
		$courseName = WSTAddslashes(I("courseName"));
		$shopName = WSTAddslashes(I("shopName"));
		$userName = WSTAddslashes(I("userName"));
		$pcurr = (int)I("pcurr",0);

		$sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName 
		        FROM __PREFIX__orders o,__PREFIX__shops sp WHERE o.orderFlag=1 and o.userId = $userId AND o.orderStatus =3 AND o.shopId=sp.shopId ";
		if($orderNo!=""){
			$sql .= " AND o.orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND o.userName like '%$userName%'";
		}
		if($shopName!=""){
			$sql .= " AND sp.shopName like '%$shopName%'";
		}
		$sql .= " order by o.orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);	
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
	        $sql = "SELECT og.courseId,og.courseName,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";	
			$glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
    /**
	 * 获取待发货订单
	 */
	public function queryDeliveryByPage($obj){
		$userId = (int)$obj["userId"];
		$orderNo = WSTAddslashes(I("orderNo"));
		$orderStatus = (int)I("orderStatus",0);
		$courseName = WSTAddslashes(I("courseName"));
		$shopName = WSTAddslashes(I("shopName"));
		$userName = WSTAddslashes(I("userName"));
		$pcurr = (int)I("pcurr",0);

		$sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName 
		        FROM __PREFIX__orders o,__PREFIX__shops sp 
		        WHERE o.orderFlag=1 and o.userId = $userId AND o.orderStatus in ( 0,1,2 ) AND o.shopId=sp.shopId ";
		if($orderNo!=""){
			$sql .= " AND o.orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND o.userName like '%$userName%'";
		}
		if($shopName!=""){
			$sql .= " AND sp.shopName like '%$shopName%'";
		}
		$sql .= " order by o.orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);
       	
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
	        $sql = "SELECT og.courseId,og.courseName,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";	
			$glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
    /**
	 * 获取退款
	 */
	public function queryRefundByPage($obj){
		$userId = (int)$obj["userId"];
		$orderNo = WSTAddslashes(I("orderNo"));
		$orderStatus = (int)I("orderStatus",0);
		$courseName = WSTAddslashes(I("courseName"));
		$shopName = WSTAddslashes(I("shopName"));
		$userName = WSTAddslashes(I("userName"));
		$sdate = WSTAddslashes(I("sdate"));
		$edate = WSTAddslashes(I("edate"));
		$pcurr = (int)I("pcurr",0);
		//必须是在线支付的才允许退款

		$sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName ,oc.complainId
		        FROM __PREFIX__orders o left join __PREFIX__order_complains oc on oc.orderId=o.orderId,__PREFIX__shops sp 
		        WHERE o.orderFlag=1 and o.userId = $userId AND (o.orderStatus in (-3,-4,-5) or (o.orderStatus in (-1,-4,-6,-7) and payType =1 AND o.isPay =1)) AND o.shopId=sp.shopId ";
		if($orderNo!=""){
			$sql .= " AND o.orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND o.userName like '%$userName%'";
		}
		if($shopName!=""){
			$sql .= " AND sp.shopName like '%$shopName%'";
		}
		$sql .= " order by o.orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);	
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
	        $sql = "SELECT og.courseId,og.courseName,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";	
			$glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
	
	/**
	 * 获取取消的订单
	 */
	public function queryCancelOrders($obj){
		$userId = (int)$obj["userId"];
		$orderNo = WSTAddslashes(I("orderNo"));
		$orderStatus = (int)I("orderStatus",0);
		$courseName = WSTAddslashes(I("courseName"));
		$shopName = WSTAddslashes(I("shopName"));
		$userName = WSTAddslashes(I("userName"));
		$pcurr = (int)I("pcurr",0);

		$sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName 
		        FROM __PREFIX__orders o,__PREFIX__shops sp 
		        WHERE o.orderFlag=1 and o.userId = $userId AND o.orderStatus in (-1,-6,-7) AND o.shopId=sp.shopId ";
		if($orderNo!=""){
			$sql .= " AND o.orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND o.userName like '%$userName%'";
		}
		if($shopName!=""){
			$sql .= " AND sp.shopName like '%$shopName%'";
		}
		$sql .= " order by o.orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);	
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
	        $sql = "SELECT og.courseId,og.courseName,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";	
			$glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
	
	/**
	 * 获取待评价交易
	 */
	public function queryAppraiseByPage($obj){
		$userId = (int)$obj["userId"];
		$orderNo = WSTAddslashes(I("orderNo"));
		$courseName = WSTAddslashes(I("courseName"));
		$shopName = WSTAddslashes(I("shopName"));
		$userName = WSTAddslashes(I("userName"));
		$pcurr = (int)I("pcurr",0);
		$sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
		        o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName ,oc.complainId
		        FROM __PREFIX__orders o left join __PREFIX__order_complains oc on oc.orderId=o.orderId,__PREFIX__shops sp WHERE o.orderFlag=1 and o.isAppraises=0 and o.userId = $userId AND o.shopId=sp.shopId ";	
		if($orderNo!=""){
			$sql .= " AND o.orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND o.userName like '%$userName%'";
		}
		if($shopName!=""){
			$sql .= " AND sp.shopName like '%$shopName%'";
		}
		$sql .= " AND o.orderStatus = 4";
		$sql .= " order by o.orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);	
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
	        $sql = "SELECT og.courseId,og.courseName,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";	
			$glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
	
	/**
	 * 获取已完成交易
	 */
	public function queryCompleteOrders($obj){
		$userId = (int)$obj["userId"];
		$orderNo = WSTAddslashes(I("orderNo"));
		$courseName = WSTAddslashes(I("courseName"));
		$shopName = WSTAddslashes(I("shopName"));
		$userName = WSTAddslashes(I("userName"));
		$pcurr = (int)I("pcurr",0);
		$sql = "SELECT o.orderId,o.orderNo,o.shopId,o.orderStatus,o.userName,o.totalMoney,o.realTotalMoney,
				o.createTime,o.payType,o.isRefund,o.isAppraises,sp.shopName ,oc.complainId
				FROM __PREFIX__orders o left join __PREFIX__order_complains oc on oc.orderId=o.orderId,__PREFIX__shops sp WHERE o.orderFlag=1 and o.isAppraises=1 and o.userId = $userId AND o.shopId=sp.shopId ";
		if($orderNo!=""){
		$sql .= " AND o.orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND o.userName like '%$userName%'";
		}
		if($shopName!=""){
			$sql .= " AND sp.shopName like '%$shopName%'";
		}
		$sql .= " AND o.orderStatus = 4";
		$sql .= " order by o.orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds = array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
			}
			//获取涉及的课程
			$sql = "SELECT og.courseId,og.courseName,og.courseThums,og.orderId FROM __PREFIX__order_course og
					WHERE og.orderId in (".implode(',',$orderIds).")";
			$glist = $this->query($sql);
			$courselist = array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$order["courselist"] = $courselist[$order['orderId']];
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
	
	/**
	 * 取消订单
	 */
	public function orderCancel($obj){		
		$userId = (int)$obj["userId"];
		$orderId = (int)$obj["orderId"];
		$rsdata = array('status'=>-1);
		//判断订单状态，只有符合状态的订单才允许改变
		$sql = "SELECT orderId,orderNo,orderStatus,useScore FROM __PREFIX__orders WHERE orderFlag=1 and orderId = $orderId and orderFlag = 1 and userId=".$userId;		
		$rsv = $this->queryRow($sql);
		$cancelStatus = array(0,1,2,-2);//未受理,已受理,打包中,待付款订单
		if(!in_array($rsv["orderStatus"], $cancelStatus))return $rsdata;
		//如果是未受理和待付款的订单直接改为"用户取消【受理前】"，已受理和打包中的则要改成"用户取消【受理后-商家未知】"，后者要给商家知道有这么一回事，然后再改成"用户取消【受理后-商家已知】"的状态
		$orderStatus = -6;//取对商家影响最小的状态
		if($rsv["orderStatus"]==0 || $rsv["orderStatus"]==-2)$orderStatus = -1;
		if($orderStatus==-6 && I('rejectionRemarks')=='')return $rsdata;//如果是受理后取消需要有原因
		$sql = "UPDATE __PREFIX__orders set orderStatus = ".$orderStatus." WHERE orderFlag=1 and orderId = $orderId and userId=".$userId;	
		$rs = $this->execute($sql);		
		
		$sql = "select ord.deliverType, ord.orderId, og.courseId ,og.courseId, og.courseNums 
				from __PREFIX__orders ord , __PREFIX__order_course og 
				WHERE ord.orderFlag=1 and ord.orderId = og.orderId AND ord.orderId = $orderId";
		$ocourseList = $this->query($sql);
		//获取课程库存
		for($i=0;$i<count($ocourseList);$i++){
			$scourse = $ocourseList[$i];
			$sql="update __PREFIX__course set courseStock=courseStock+".$scourse['courseNums']." where courseId=".$scourse["courseId"];
			$this->execute($sql);
		}
		$sql="Delete From __PREFIX__order_reminds where orderId=".$orderId." AND remindType=0";
		$this->execute($sql);
		
		if($rsv["useScore"]>0){
			$sql = "UPDATE __PREFIX__users set userScore=userScore+".$rsv["useScore"]." WHERE userId=".$userId;
			$this->execute($sql);
			
			$data = array();
			$m = M('user_score');
			$data["userId"] = $userId;
			$data["score"] = $rsv["useScore"];
			$data["dataSrc"] = 3;
			$data["dataId"] = $orderId;
			$data["dataRemarks"] = "取消订单返还";
			$data["scoreType"] = 1;
			$data["createTime"] = date('Y-m-d H:i:s');
			$m->add($data);
		}
		$data = array();
		$m = M('log_orders');
		$data["orderId"] = $orderId;
		$data["logContent"] = "用户已取消订单".(($orderStatus==-6)?"：".I('rejectionRemarks'):"");
		$data["logUserId"] = $userId;
		$data["logType"] = 0;
		$data["logTime"] = date('Y-m-d H:i:s');
		$ra = $m->add($data);
		$rsdata["status"] = $ra;
		return $rsdata;
		
	}
	/**
	 * 用户确认收货
	 */
	public function orderConfirm ($obj){		
		$userId = (int)$obj["userId"];
		$orderId = (int)$obj["orderId"];
		$type = (int)$obj["type"];
		$rsdata = array();
		$sql = "SELECT orderId,orderNo,orderScore,orderStatus,poundageRate,poundageMoney,shopId,useScore,scoreMoney FROM __PREFIX__orders WHERE orderFlag=1 and orderId = $orderId and userId=".$userId;		
		$rsv = $this->queryRow($sql);
		if($rsv["orderStatus"]!=3){
			$rsdata["status"] = -1;
			return $rsdata;
		}		
        //收货则给用户增加积分
        if($type==1){
        	$sql = "UPDATE __PREFIX__orders set orderStatus = 4,receiveTime='".date("Y-m-d H:i:s")."'  WHERE orderFlag=1 and orderId = $orderId and userId=".$userId;			
        	$this->execute($sql);
        	
        	//修改课程销量
        	$sql = "select og.courseId, sum(og.courseNums) gcnt FROM __PREFIX__order_course og, __PREFIX__orders o WHERE o.orderFlag=1 and og.orderId = o.orderId AND o.orderId=$orderId AND o.userId=".$userId." group by og.courseId";
        	$ocourse = $this->query($sql);
        	for ($i = 0; $i < count($ocourse); $i++) {
        		$row = $ocourse[$i];
        		$sql = "UPDATE __PREFIX__course SET saleCount=saleCount+".$row['gcnt']." WHERE courseId=".$row['courseId'];
        		$this->execute($sql);
        	}
        	//修改积分
        	if($GLOBALS['CONFIG']['isOrderScore']==1 && $rsv["orderScore"]>0){
	        	$sql = "UPDATE __PREFIX__users set userScore=userScore+".$rsv["orderScore"].",userTotalScore=userTotalScore+".$rsv["orderScore"]." WHERE userId=".$userId;
	        	$this->execute($sql);
	        	
	        	$data = array();
	        	$m = M('user_score');
	        	$data["userId"] = $userId;
	        	$data["score"] = $rsv["orderScore"];
	        	$data["dataSrc"] = 1;
	        	$data["dataId"] = $orderId;
	        	$data["dataRemarks"] = "交易获得";
	        	$data["scoreType"] = 1;
	        	$data["createTime"] = date('Y-m-d H:i:s');
	        	$m->add($data);
        	}
        	//积分支付支出
        	if($rsv["scoreMoney"]>0){
        		$data = array();
        		$m = M('log_sys_moneys');
        		$data["targetType"] = 0;
        		$data["targetId"] = $userId;
        		$data["dataSrc"] = 2;
        		$data["dataId"] = $orderId;
        		$data["moneyRemark"] = "订单【".$rsv["orderNo"]."】支付 ".$rsv["useScore"]." 个积分，支出 ￥".$rsv["scoreMoney"];
        		$data["moneyType"] = 2;
        		$data["money"] = $rsv["scoreMoney"];
        		$data["createTime"] = date('Y-m-d H:i:s');
        		$data["dataFlag"] = 1;
        		$m->add($data);
        	}
        	//收取订单佣金
        	if($rsv["poundageMoney"]>0){
        		$data = array();
        		$m = M('log_sys_moneys');
        		$data["targetType"] = 1;
        		$data["targetId"] = $rsv["shopId"];
        		$data["dataSrc"] = 1;
        		$data["dataId"] = $orderId;
        		$data["moneyRemark"] = "收取订单【".$rsv["orderNo"]."】".$rsv["poundageRate"]."%的佣金 ￥".$rsv["poundageMoney"];
        		$data["moneyType"] = 1;
        		$data["money"] = $rsv["poundageMoney"];
        		$data["createTime"] = date('Y-m-d H:i:s');
        		$data["dataFlag"] = 1;
        		$m->add($data);
        	}
        	
        }else{
        	if(I('rejectionRemarks')=='')return $rsdata;//如果是拒收的话需要填写原因
        	$sql = "UPDATE __PREFIX__orders set orderStatus = -3 WHERE orderFlag=1 and orderId = $orderId and userId=".$userId;			
        	$this->execute($sql);
        }
        //增加记录
		$data = array();
		$m = M('log_orders');
		$data["orderId"] = $orderId;
		$data["logContent"] = ($type==1)?"用户已收货":"用户拒收：".I('rejectionRemarks');
		$data["logUserId"] = $userId;
		$data["logType"] = 0;
		$data["logTime"] = date('Y-m-d H:i:s');
		$ra = $m->add($data);
		$rsdata["status"] = $ra;;
		return $rsdata;
	}
	
    /**
     * 获取订单详情
     */
	public function getOrderDetails($obj){
		$userId = (int)$obj["userId"];
		$shopId = (int)$obj["shopId"];
		$orderId = (int)$obj["orderId"];
		$data = array();
		$sql = "SELECT * FROM __PREFIX__orders WHERE orderFlag=1 and orderId = $orderId and (userId=".$userId." or shopId=".$shopId.")";	
		$order = $this->queryRow($sql);
		if(empty($order))return $data;
		$data["order"] = $order;
		$sql = "select og.orderId, og.courseId ,g.courseSn, og.courseNums, og.courseName , og.coursePrice shopPrice,og.courseThums,og.courseAttrName,og.courseAttrName 
				from __PREFIX__course g , __PREFIX__order_course og 
				WHERE g.courseId = og.courseId AND og.orderId = $orderId";
		$course = $this->query($sql);
		$data["courseList"] = $course;

		$sql = "SELECT * FROM __PREFIX__log_orders WHERE orderId = $orderId ";	
		$logs = $this->query($sql);
		$data["logs"] = $logs;
		
		return $data;
		
	}
	/**
	 * 获取用户指定状态的订单数目
	 */
	public function getUserOrderStatusCount($obj){
		$userId = (int)$obj["userId"];
		$data = array();
		$sql = "select orderStatus,COUNT(*) cnt from __PREFIX__orders WHERE orderStatus in (0,1,2,3) and orderFlag=1 and userId = $userId GROUP BY orderStatus";
		$olist = $this->query($sql);
		$data = array('-3'=>0,'-2'=>0,'2'=>0,'3'=>0,'4'=>0);
		for($i=0;$i<count($olist);$i++){
			$row = $olist[$i];
			if($row["orderStatus"]==0 || $row["orderStatus"]==1 || $row["orderStatus"]==2){
				$row["orderStatus"] = 2;
			}
			$data[$row["orderStatus"]] = $data[$row["orderStatus"]]+$row["cnt"];
		}
		//获取未支付订单
		$sql = "select COUNT(*) cnt from __PREFIX__orders WHERE orderStatus = -2 and isRefund=0 and payType=1 and orderFlag=1 and isPay = 0 and needPay >0 and userId = $userId";
		$olist = $this->query($sql);
		$data[-2] = $olist[0]['cnt'];
		
		//获取退款订单
		$sql = "select COUNT(*) cnt from __PREFIX__orders WHERE orderStatus in (-3,-4,-6,-7) and isRefund=0 and payType=1 and orderFlag=1 and userId = $userId";
		$olist = $this->query($sql);
		$data[-3] = $olist[0]['cnt'];
		//获取待评价订单
		$sql = "select COUNT(*) cnt from __PREFIX__orders WHERE orderStatus =4 and isAppraises=0 and orderFlag=1 and userId = $userId";
		$olist = $this->query($sql);
		$data[4] = $olist[0]['cnt'];
		
		//获取商城信息
		$sql = "select count(*) cnt from __PREFIX__messages WHERE  receiveUserId=".$userId." and msgStatus=0 and msgFlag=1 ";
		$olist = $this->query($sql);
		$data[100000] = empty($olist)?0:$olist[0]['cnt'];
		
		return $data;
		
	}
	
	/**
	 * 获取用户指定状态的订单数目
	 */
	public function getShopOrderStatusCount($obj){
		$shopId = (int)$obj["shopId"];
		$rsdata = array();
		//待受理订单
		$sql = "SELECT COUNT(*) cnt FROM __PREFIX__orders WHERE orderFlag=1 and shopId = $shopId AND orderStatus = 0 ";
		$olist = $this->queryRow($sql);
		$rsdata[0] = $olist['cnt'];
		
		//取消-商家未知的 / 拒收订单
		$sql = "SELECT COUNT(*) cnt FROM __PREFIX__orders WHERE orderFlag=1 and shopId = $shopId AND orderStatus in (-3,-6)";
		$olist = $this->queryRow($sql);
		$rsdata[5] = $olist['cnt'];
		$rsdata[100] = $rsdata[0]+$rsdata[5];
		
		//获取商城信息
		$sql = "select count(*) cnt from __PREFIX__messages WHERE receiveUserId=".(int)$obj["userId"]." and msgStatus=0 and msgFlag=1 ";
		$olist = $this->query($sql);
		$rsdata[100000] = empty($olist)?0:$olist[0]['cnt'];
		
		return $rsdata;
	
	}
	
	public function queryByShopOrder($obj){
		
		$shopId = $obj["shopId"];
		$pcurr = (int)I("p",0);
		$sql = "SELECT o.* FROM __PREFIX__orders o
		WHERE shopId = $shopId AND orderFlag=1 order by orderId desc";
		$pages = $this->pageQuery($sql,$pcurr);
		$orderList = $pages["root"];
		if(count($orderList)>0){
			$orderIds=$userIds= array();
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				$orderIds[] = $order["orderId"];
				  $userIds[]=$order["userId"];
			}
			//dump($userIds);
			//获取涉及的课程
			$sql = "SELECT og.courseId,og.courseName,og.coursePrice,og.courseThums,og.orderId,og.buyerName  FROM __PREFIX__order_course og   WHERE og.orderId in (".implode(',',$orderIds).")";
			$glist = $this->query($sql);
			$userlist=$ulist= array();
			for($i=0;$i<count($glist);$i++){
				$course = $glist[$i];
				$courselist[$course["orderId"]][] = $course;
			}
			/* $sqla="SELECT su.LoginName,su.userPhoto,su.userName,su.userId  FROM  __PREFIX__users su WHERE su.userId in (".implode(',',$userIds).")" ;
			$ulist=$this->query($sqla);
		   dump($ulist);
		for($i=0;$i<count($ulist);$i++){
			    $uname = $ulist[$i];
				$ulist[$uname["userId"]][] =$uname;
			} */
			
			//$courselist =array_merge($courselist[$course["orderId"]],$courselist[$uname["userId"]]);
			//dump($courselist);
			//exit;
			
			
			
			//放回分页数据里
			for($i=0;$i<count($orderList);$i++){
				$order = $orderList[$i];
				
				 $order["courselist"] = $courselist[$order['orderId']];
				// $order["ulist"]= $ulist[$order['userId']];
				
				
				$pages["root"][$i] = $order;
			}
		}
		return $pages;
	}
	
	
	
	
	
	
	
	
	
	/**
	 * 获取商家订单列表
	 */
	public function queryShopOrders($obj){		
		$userId = (int)$obj["userId"];
		$shopId = (int)$obj["shopId"];
		$pcurr = (int)I("pcurr",0);
		$orderStatus = (int)I("statusMark");
		
		$orderNo = WSTAddslashes(I("orderNo"));
		$userName = WSTAddslashes(I("userName"));
		$userAddress = WSTAddslashes(I("userAddress"));
		$rsdata = array();
		$sql = "SELECT orderNo,orderId,userId,userName,userAddress,totalMoney,realTotalMoney,orderStatus,createTime FROM __PREFIX__orders WHERE  orderFlag=1 and shopId = $shopId ";
		if($orderStatus==5){
			$sql.=" AND orderStatus in (-3,-4,-5,-6,-7)";
		}else{
			$sql.=" AND orderStatus = $orderStatus ";	
		}
		if($orderNo!=""){
			$sql .= " AND orderNo like '%$orderNo%'";
		}
		if($userName!=""){
			$sql .= " AND userName like '%$userName%'";
		}
		if($userAddress!=""){
			$sql .= " AND userAddress like '%$userAddress%'";
		}
		$sql.=" order by orderId desc ";
		$data = $this->pageQuery($sql,$pcurr);
		//获取取消/拒收原因
		$orderIds = array();
		$noReadrderIds = array();
		foreach ($data['root'] as $key => $v){	
			if($v['orderStatus']==-6)$noReadrderIds[] = $v['orderId'];
			$sql = "select logContent from __PREFIX__log_orders where orderId =".$v['orderId']." and logType=0 and logUserId=".$v['userId']." order by logId desc limit 1";
			$ors = $this->query($sql);
			$data['root'][$key]['rejectionRemarks'] = $ors[0]['logContent'];
		}
		
		//要对用户取消【-6】的状态进行处理,表示这一条取消信息商家已经知道了
		if($orderStatus==5 && count($noReadrderIds)>0){
			$sql = "UPDATE __PREFIX__orders set orderStatus=-7 WHERE orderFlag=1 and shopId = $shopId AND orderId in (".implode(',',$noReadrderIds).")AND orderStatus = -6 ";
			$this->execute($sql);
		}
		return $data;
	}
	
	/**
	 * 商家受理订单-只能受理【未受理】的订单
	 */
	public function shopOrderAccept ($obj){		
		$userId = (int)$obj["userId"];
		$orderId = (int)$obj["orderId"];
		$shopId = (int)$obj["shopId"];
		$rsdata = array();
		$sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderFlag=1 and orderId = $orderId AND orderFlag=1 and shopId=".$shopId;		
		$rsv = $this->queryRow($sql);
		if($rsv["orderStatus"]!=0){
			$rsdata["status"] = -1;
			return $rsdata;
		}

		$sql = "UPDATE __PREFIX__orders set orderStatus = 1 WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
		$rs = $this->execute($sql);		

		$data = array();
		$m = M('log_orders');
		$data["orderId"] = $orderId;
		$data["logContent"] = "商家已受理订单";
		$data["logUserId"] = $userId;
		$data["logType"] = 0;
		$data["logTime"] = date('Y-m-d H:i:s');
		$ra = $m->add($data);
		$rsdata["status"] = $ra;
		return $rsdata;
	}
	
    /**
	 * 商家批量受理订单-只能受理【未受理】的订单
	 */
	public function batchShopOrderAccept(){		
		$USER = session('WST_USER');
		$userId = (int)$USER["userId"];
		$orderIds = self::formatIn(",", I("orderIds"));
		$shopId = (int)$USER["shopId"];
		if($orderIds=='')return array('status'=>-2);
		$orderIds = explode(',',$orderIds);
		$orderNum = count($orderIds);
		$editOrderNum = 0;
		foreach ($orderIds as $orderId){
			if($orderId=='')continue;//订单号为空则跳过
			$sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag=1 and shopId=".$shopId;		
			$rsv = $this->queryRow($sql);
			if($rsv["orderStatus"]!=0)continue;//订单状态不符合则跳过
			$sql = "UPDATE __PREFIX__orders set orderStatus = 1 WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
			$rs = $this->execute($sql);		
	
			$data = array();
			$m = M('log_orders');
			$data["orderId"] = $orderId;
			$data["logContent"] = "商家已受理订单";
			$data["logUserId"] = $userId;
			$data["logType"] = 0;
			$data["logTime"] = date('Y-m-d H:i:s');
			$ra = $m->add($data);
			$editOrderNum++;
		}
		if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
		if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
		return array('status'=>1);
	}
	
	/**
	 * 商家打包订单-只能处理[受理]的订单
	 */
	public function shopOrderProduce ($obj){		
		$userId = (int)$obj["userId"];
		$shopId = (int)$obj["shopId"];
		$orderId = (int)$obj["orderId"];
		$rsdata = array();
		$sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;		
		$rsv = $this->queryRow($sql);
		if($rsv["orderStatus"]!=1){
			$rsdata["status"] = -1;
			return $rsdata;
		}

		$sql = "UPDATE __PREFIX__orders set orderStatus = 2 WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
		$rs = $this->execute($sql);		
		$data = array();
		$m = M('log_orders');
		$data["orderId"] = $orderId;
		$data["logContent"] = "订单打包中";
		$data["logUserId"] = $userId;
		$data["logType"] = 0;
		$data["logTime"] = date('Y-m-d H:i:s');
		$ra = $m->add($data);
		$rsdata["status"] = $ra;;
		return $rsdata;
	}
	
    /**
	 * 商家批量打包订单-只能处理[受理]的订单
	 */
	public function batchShopOrderProduce (){		
		$USER = session('WST_USER');
		$userId = (int)$USER["userId"];
		$orderIds = self::formatIn(",", I("orderIds"));
		$shopId = (int)$USER["shopId"];
		if($orderIds=='')return array('status'=>-2);
		$orderIds = explode(',',$orderIds);
		$orderNum = count($orderIds);
		$editOrderNum = 0;
		foreach ($orderIds as $orderId){
			if($orderId=='')continue;//订单号为空则跳过
			$sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;		
			$rsv = $this->queryRow($sql);
			if($rsv["orderStatus"]!=1)continue;//订单状态不符合则跳过
	
			$sql = "UPDATE __PREFIX__orders set orderStatus = 2 WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
			$rs = $this->execute($sql);		
			$data = array();
			$m = M('log_orders');
			$data["orderId"] = $orderId;
			$data["logContent"] = "订单打包中";
			$data["logUserId"] = $userId;
			$data["logType"] = 0;
			$data["logTime"] = date('Y-m-d H:i:s');
			$ra = $m->add($data);
			$editOrderNum++;
		}
		if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
		if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
		return array('status'=>1);
	}
	
	/**
	 * 商家发货配送订单
	 */
	public function shopOrderDelivery ($obj){		
		$userId = (int)$obj["userId"];
		$orderId = (int)$obj["orderId"];
		$shopId = (int)$obj["shopId"];
		$rsdata = array();
		$sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;		
		$rsv = $this->queryRow($sql);
		if($rsv["orderStatus"]!=2){
			$rsdata["status"] = -1;
			return $rsdata;
		}

		$sql = "UPDATE __PREFIX__orders set orderStatus = 3,deliveryTime='".date('Y-m-d H:i:s')."' WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
		$rs = $this->execute($sql);		

		$data = array();
		$m = M('log_orders');
		$data["orderId"] = $orderId;
		$data["logContent"] = "商家已发货";
		$data["logUserId"] = $userId;
		$data["logType"] = 0;
		$data["logTime"] = date('Y-m-d H:i:s');
		$ra = $m->add($data);
		$rsdata["status"] = $ra;;
		return $rsdata;
	}
	
    /**
	 * 商家发货配送订单
	 */
	public function batchShopOrderDelivery ($obj){		
		$USER = session('WST_USER');
		$userId = (int)$USER["userId"];
		$orderIds = self::formatIn(",",I("orderIds"));
		$shopId = (int)$USER["shopId"];
		if($orderIds=='')return array('status'=>-2);
		$orderIds = explode(',',$orderIds);
		$orderNum = count($orderIds);
		$editOrderNum = 0;
		foreach ($orderIds as $orderId){
			if($orderId=='')continue;//订单号为空则跳过
			$sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;		
			$rsv = $this->queryRow($sql);
			if($rsv["orderStatus"]!=2)continue;//状态不符合则跳过
	
			$sql = "UPDATE __PREFIX__orders set orderStatus = 3,deliveryTime='".date('Y-m-d H:i:s')."' WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
			$rs = $this->execute($sql);		
	
			$data = array();
			$m = M('log_orders');
			$data["orderId"] = $orderId;
			$data["logContent"] = "商家已发货";
			$data["logUserId"] = $userId;
			$data["logType"] = 0;
			$data["logTime"] = date('Y-m-d H:i:s');
			$ra = $m->add($data);
		    $editOrderNum++;
		}
		if($editOrderNum==0)return array('status'=>-1);//没有符合条件的执行操作
		if($editOrderNum<$orderNum)return array('status'=>-2);//只有部分订单符合操作
		return array('status'=>1);
	}
	
	/**
	 * 商家确认收货
	 */
	public function shopOrderReceipt ($obj){		
		$userId = (int)$obj["userId"];
		$shopId = (int)$obj["shopId"];
		$orderId = (int)$obj["orderId"];
		$rsdata = array();
		$sql = "SELECT orderId,orderNo,orderStatus FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag =1 and shopId=".$shopId;		
		$rsv = $this->queryRow($sql);
		if($rsv["orderStatus"]!=4){
			$rsdata["status"] = -1;
			return $rsdata;
		}

		$sql = "UPDATE __PREFIX__orders set orderStatus = 5 WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
		$rs = $this->execute($sql);		

		$data = array();
		$m = M('log_orders');
		$data["orderId"] = $orderId;
		$data["logContent"] = "商家确认已收货，订单完成";
		$data["logUserId"] = $userId;
		$data["logType"] = 0;
		$data["logTime"] = date('Y-m-d H:i:s');
		$ra = $m->add($data);
		$rsdata["status"] = $ra;;
		return $rsdata;
	}
	/**
	 * 商家确认拒收/不同意拒收
	 */
	public function shopOrderRefund ($obj){		
		$userId = (int)$obj["userId"];
		$orderId = (int)$obj["orderId"];
		$shopId = (int)$obj["shopId"];
		$type = (int)I('type');
		$rsdata = array();
		$sql = "SELECT orderId,orderNo,orderStatus,useScore,userId FROM __PREFIX__orders WHERE orderId = $orderId AND orderFlag = 1 and shopId=".$shopId;		
		$rsv = $this->queryRow($sql);
		if($rsv["orderStatus"]!= -3){
			$rsdata["status"] = -1;
			return $rsdata;
		}
		//同意拒收
        if($type==1){
			$sql = "UPDATE __PREFIX__orders set orderStatus = -4 WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
			$rs = $this->execute($sql);
			//加回库存
			if($rs>0){
				$sql = "SELECT courseId,courseNums,courseAttrId from __PREFIX__order_course WHERE orderId = $orderId";
				$oglist = $this->query($sql);
				foreach ($oglist as $key => $ocourse) {
					$courseId = $ocourse["courseId"];
					$courseNums = $ocourse["courseNums"];
					$courseAttrId = $ocourse["courseAttrId"];
					$sql = "UPDATE __PREFIX__course set courseStock = courseStock+$courseNums WHERE courseId = $courseId";
					$this->execute($sql);
					if($courseAttrId>0){
						$sql = "UPDATE __PREFIX__course_attributes set attrStock = attrStock+$courseNums WHERE id = $courseAttrId";
						$this->execute($sql);
					}
				}
				
				if($rsv["useScore"]>0){
					$sql = "UPDATE __PREFIX__users set userScore=userScore+".$rsv["useScore"]." WHERE userId=".$rsv["userId"];
					$this->execute($sql);
						
					$data = array();
					$m = M('user_score');
					$data["userId"] = $userId;
					$data["score"] = $rsv["useScore"];
					$data["dataSrc"] = 4;
					$data["dataId"] = $rsv["userId"];
					$data["dataRemarks"] = "拒收订单返还";
					$data["scoreType"] = 1;
					$data["createTime"] = date('Y-m-d H:i:s');
					$m->add($data);
				}
			}	
        }else{//不同意拒收
        	if(I('rejectionRemarks')=='')return $rsdata;//不同意拒收必须填写原因
        	$sql = "UPDATE __PREFIX__orders set orderStatus = -5 WHERE orderFlag=1 and orderId = $orderId and shopId=".$shopId;		
			$rs = $this->execute($sql);
        }
		$data = array();
		$m = M('log_orders');
		$data["orderId"] = $orderId;
		$data["logContent"] = ($type==1)?"商家同意拒收":"商家不同意拒收：".I('rejectionRemarks');
		$data["logUserId"] = $userId;
		$data["logType"] = 0;
		$data["logTime"] = date('Y-m-d H:i:s');
		$ra = $m->add($data);
		$rsdata["status"] = $ra;;
		return $rsdata;
	}
	
	/**
	 * 检查订单是否已支付
	 */
	public function checkOrderPay ($obj){
		$userId = (int)$obj["userId"];
		$orderId = (int)I("orderId");
		if($orderId>0){
			$sql = "SELECT orderId,orderNo FROM __PREFIX__orders WHERE userId = $userId AND orderId = $orderId AND orderFlag = 1 AND orderStatus = -2 AND isPay = 0 ";
		}else{
			$orderunique = session("WST_ORDER_UNIQUE");
			$sql = "SELECT orderId,orderNo FROM __PREFIX__orders WHERE userId = $userId AND orderunique = '$orderunique' AND orderFlag = 1 AND orderStatus = -2 AND isPay = 0 ";
		}
		$rsv = $this->query($sql);
		$oIds = array();
		for($i=0;$i<count($rsv);$i++){
			$oIds[] = $rsv[$i]["orderId"];
		}
		$orderIds = implode(",",$oIds);
		$data = array();
		if(count($rsv)>0){
			$sql = "SELECT og.courseId,og.courseName,og.courseAttrName,g.courseStock,og.courseNums, og.courseAttrId, ga.attrStock FROM  __PREFIX__course g ,__PREFIX__order_course og
					left join __PREFIX__course_attributes ga on ga.courseId=og.courseId and og.courseAttrId=ga.id
					WHERE og.courseId = g.courseId and og.orderId in($orderIds)";
			$glist = $this->query($sql);
			if(count($glist)>0){
				$rlist = array();
				foreach ($glist as $course) {
					if($course["courseAttrId"]>0){
						if($course["attrStock"]<$course["courseNums"]){
							$rlist[] = $course;
						}
					}else{
						if($course["courseStock"]<$course["courseNums"]){
							$rlist[] = $course;
						}
					}
				}
				if(count($rlist)>0){
					$data["status"] = -2;
					$data["rlist"] = $rlist;
				}else{
					$data["status"] = 1;
				}
			}else{
				$data["status"] = 1;
			}
		}else{
			$data["status"] = -1;
		}
		return $data;
	}
}