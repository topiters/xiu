<?php
namespace Home\Action;
/**
*  购物车文件
* ==============================================
* 版权所有 2015-2016 http://www.chunni168.com
* ----------------------------------------------
* 这不是一个自由软件，未经授权不许任何使用和传播。
* ==============================================
* @date: 2016-11-18
* @author: top_iter、lnrp
* @email:2504585798@qq.com
* @version:1.0
*/
class CartAction extends BaseAction {
	/**
	 * 跳到购物车列表
	 */
    public function toCart(){
   		$m = D('Home/Cart');
		$cartInfo = $m->getCartInfo();

		//dump($cartInfo);die;
		//exit;

   		$pnow = (int)I("pnow",0);
   		$this->assign('cartInfo',$cartInfo);
  // 	dump($v);
   	//	exit;
   		$this->display('default/cart_pay_list');//

    }
    
    /**
     * 添加商品到购物车(ajax)
     */
	public function addToCartAjax(){
   		$m = D('Home/Cart');
   		$rs = $m->addToCart();
   		$this->ajaxReturn($rs);
    }
    
    /**
     * 添加优惠套餐到购物车(ajax)
     */
    public function addCartPackage(){
    	$m = D('Home/Cart');
    	$rs = $m->addCartPackage();
    	$this->ajaxReturn($rs);
    }
    
    /**
     * 修改购物车商品
     * 
     */
    public function changeCartCourse(){
    	$m = D('Home/Cart');
   		$res = $m->addToCart();
   		echo "{status:1}";
    }
    
	/**
	 * 获取购物车信息
	 * 
	 */
	public function getCartInfo() {
		$m = D('Home/Cart');
		$cartInfo = $m->getCartInfo();
		$axm = (int)I("axm",0);
		if($axm ==1){
			echo json_encode($cartInfo);
		}else{
			$this->assign('cartInfo',$cartInfo);
			$this->display('default/cart_pay_list');
		}
		
	}
	
	/**
	 * 获取购物车商品数量
	 */
	public function getCartGoodCnt(){
		echo json_encode(array("coursecnt"=>WSTCartNum()));
	}
    
	/**
	 * 检测购物车中商品库存
	 * 
	 */
    public function checkCartCourseStock() {
        $session = '';
        $m = D('car');
        $mcourse = D('Home/course');
        $userId = (int)session('WST_USER.userId');
        $cartcourse = array();
        $courseArr = I('id');
        //如果提交的课程id存在
        if ($courseArr) {

            if (strpos($courseArr , ',')) //in (".implode(",",$npIds).")
            {
                $sql = "select cc.*, uu.loginName from __PREFIX__cart  cc  Left JOIN    __PREFIX__users uu  ON uu.userId=cc.userId  where userId = $userId and courseId  in (" . implode("," , $courseArr) . ")";
            } else {
                $courseArr = (int)$courseArr;
                $sql = "select cc.* ,uu.loginName from __PREFIX__cart cc  Left JOIN    __PREFIX__users uu  ON uu.userId=cc.userId   where cc.userId = $userId  AND  courseId=$courseArr ";
            }
        } else {
            //不存在默认
            $sql = "select cc.* ,uu.loginName from __PREFIX__cart cc  Left JOIN    __PREFIX__users uu  ON uu.userId=cc.userId   where cc.userId = $userId";

        }

        $carInfo = $m->query($sql);

        if ($carInfo) {

            foreach ($carInfo as $k => $v) {
//                dump($v);
//                exit;
                $courseId = $v['courseId'];
                $sqla = "select cc.*, p.*  FROM __PREFIX__course cc LEFT JOIN  __PREFIX__shops p  ON  p.shopId=cc.shopId where  cc.courseId=$courseId";

                $courseInfo = $mcourse->query($sqla);
                // var_dump( $courseInfo);
                //  exit;
                if ($courseInfo) {
                    //$clength=count($courseInfo);
                    //dump( $courseInfo);
                    //exit;
                    foreach ($courseInfo as $kk => $val) {
                        //var_dump($val['shopId']);
                        //exit;

                        $orderCourse['orderNo'] = 'tax' . time() . rand(1000 , 9999);//订单号
                        $orderCourse['shopId'] = $val['shopId'];
                        $orderCourse['totalMoney'] = $val['shopPrice'];
                        $orderCourse['userId'] = $userId;
                        $orderCourse['payType'] = 1;
                        $orderCourse['createTime'] = date('Y-m-d H:i:s' , time());
                        $order_course = D('orders')->add($orderCourse);


                        if ($order_course) {
                            if ($k > 0) {
                                $sessionss .= ',' . $order_course;
                            } else {
                                $sessionss = $order_course;
                            }
                            $data['orderId'] = $order_course;
                            $data['courseId'] = $val['courseId'];
                            $data['courseNums'] = 1;
                            $data['coursePrice'] = $val['shopPrice'];
                            $data['courseName'] = $val['courseName'];
                            $data['courseThums'] = $val['courseThums'];
                            $data['buyerName'] = $v['loginName'];

                            //var_dump($data);
                            //exit;
                            $od = D('order_course')->add($data);

                            if ($od) {
                                $datas = array('isCheck' => 2);
                                D('cart')->where(array('userId' => $userId , 'courseId' => $val['courseId']))->save($datas);
                            }
                        }
                    }
                }
            }
            $carStatus['status'] = 1;
            session('order' , $sessionss);
        } else {
            $carStatus['status'] = -1;
        }
 
	   
		
		
		echo json_encode($carStatus['status']);

	}
	
	
	
	/**
	 * 删除购物车中的商品
	 * 
	 */
	public function delCartCourse(){	
		$m = D('Home/Cart');	
		$res = $m->delCartCourse();
		$cartInfo = $m->getCartInfo();
		echo json_encode($cartInfo);
	}
	
	/**
	 * 删除购物车中的商品
	 *
	 */
	public function delPckCatCourse(){
		$m = D('Home/Cart');
		$res = $m->delPckCatCourse();
		$cartInfo = $m->getCartInfo();
		echo json_encode($cartInfo);
	}
	
	/**
	 * 修改购物车中的商品数量
	 * 
	 */
	public function changeCartCourseNum(){
		
		$data = array();
		$data['courseId'] = (int)I('courseId');
		$data['isBook'] = (int)I('isBook');
		$data['courseAttrId'] = (int)I('courseAttrId');
		$course = D('Home/Course');
		$courseStock = $course->getCourseStock($data);
		$num = (int)I("num");
		if($courseStock["courseStock"]>=$num){
			$num = $num>100?100:$num;
		}else{
			$num = (int)$courseStock["courseStock"];
		}
		$m = D('Home/Cart');
		$rs = $m->changeCartCoursenum(abs($num));
		$this->ajaxReturn($courseStock);
		
	}
	
	/**
	 * 修改购物车中的商品数量
	 *
	 */
	public function changePkgCartCourseNum(){
	
		$data = array();
		$data['packageId'] = (int)I('packageId');
		$data['batchNo'] = (int)I('batchNo');
		$course = D('Home/Course');
		$courseStock = $course->getPkgCourseStock($data);
		$num = (int)I("num");
		if($courseStock["courseStock"]>=$num){
			$num = $num>100?100:$num;
		}else{
			$num = (int)$courseStock["courseStock"];
		}
		$m = D('Home/Cart');
		$rs = $m->changePkgCartCourseNum(abs($num));
		$this->ajaxReturn($courseStock);
	
	}
	
	/**
	 *去购物车结算
	 * 
	 */
	public function toCatpaylist(){	
		$m = D('Home/Cart');
		$cartInfo = $m->getCartInfo();
		$this->assign("cartInfo",$cartInfo);
		
		$this->display('default/cat_pay_list');
	}
	
	
	
	
	public function deleteCartCourse(){
		$m = D('car');
		//$mcourse = D('Home/course');
		$userId = (int)session('WST_USER.userId');
		//$cartcourse = array();
		$courseArr=I('id');
		//如果提交的课程id存在
		if($courseArr){
				
			if(strpos($courseArr,',')){
				
				//in (".implode(",",$npIds).")
				$sql = " delete  From   __PREFIX__cart  where userId = $userId  AND  courseId  in (".implode(",",$courseArr).")";
			
			}else{
				$courseArr=(int)$courseArr;
				$sql = "delete  From   __PREFIX__cart where userId = $userId  AND  courseId=$courseArr";
			}
			   $result=$m->query($sql);
			   
			//  var_dump($m->getLastSql());
			//  exit;
			
				$this->ajaxReturn(array('status'=>1));
			
			 
	
	
		}
	}
	
}