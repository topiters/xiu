<?php
namespace Home\Action;
/**
*  教师端文件
* ==============================================
* 版权所有 2015-2016 http://www.chunni168.com
* ----------------------------------------------
* 这不是一个自由软件，未经授权不许任何使用和传播。
* ==============================================
* @date: 2016-11-21
* @author: top_iter、lnrp
* @email:2504585798@qq.com
* @version:1.0
*/
class ShopsAction extends BaseAction {
	/**
     * 跳到教师端首页面
     */
	public function toShopHome(){
		$mshops = D('Home/Shops');
		$shopId = (int)I('shopId');
		//如果沒有传店铺ID进来则取默认自营店铺
		if($shopId==0){
			$areaId2 = $this->getDefaultCity();
			$shopId = $mshops->checkSelfShopId($areaId2);
		}
		$shops = $mshops->getShopInfo($shopId);
		$shops["serviceEndTime"] = str_replace('.5',':30',$shops["serviceEndTime"]);
		$shops["serviceEndTime"] = str_replace('.0',':00',$shops["serviceEndTime"]);
		$shops["serviceStartTime"] = str_replace('.5',':30',$shops["serviceStartTime"]);
		$shops["serviceStartTime"] = str_replace('.0',':00',$shops["serviceStartTime"]);
		$this->assign('shops',$shops);

		if(!empty($shops)){		
			$this->assign('shopId',$shopId);
			$this->assign('ct1',(int)I("ct1"));
			$this->assign('ct2',(int)I("ct2"));
			$this->assign('msort',(int)I("msort",1));
			$this->assign('mdesc',I("mdesc",0));
			$this->assign('sprice',I("sprice"));//上架开始时间
			$this->assign('eprice',I("eprice"));//上架结束时间
			$this->assign('goodsName',urldecode(I("goodsName")));//上架结束时间
					
			$mshopscates = D('Home/ShopsCats');
			$shopscates = $mshopscates->getShopCateList($shopId);
			$this->assign('shopscates',$shopscates);
			
			$mgoods = D('Home/Goods');
			$shopsgoods = $mgoods->getShopsGoods($shopId);
			$this->assign('shopsgoods',$shopsgoods);
			//获取评分
			$obj = array();
			$obj["shopId"] = $shopId;
			$shopScores = $mshops->getShopScores($obj);
		
			$this->assign("shopScores",$shopScores);
			
			$m = D('Home/Favorites');
			$this->assign("favoriteShopId",$m->checkFavorite($shopId,1));
			$this->assign('actionName',ACTION_NAME);
		
			$this->assign('isSelf',$shops["isSelf"]);
		
		}
        $this->display("default/shop_home");
	}
	/**
     * 跳到店铺街
     */
	public function toShopStreet(){
		$areas= D('Home/Areas');
		$areaId2 = $this->getDefaultCity();
   		$areaList = $areas->getDistricts($areaId2);
   		$mshops = D('Home/Shops');
   		$obj = array();
   		if((int)cookie("bstreesAreaId3")){
   			$obj["areaId3"] = (int)cookie("bstreesAreaId3");
   		}else{
   			$obj["areaId3"] = ((int)I('areaId3')>0)?(int)I('areaId3'):$areaList[0]['areaId'];
   			cookie("bstreesAreaId3",$obj["areaId3"]);
   		}

  		$this->assign('areaId3',$obj["areaId3"]);
   		$this->assign('keyWords',I("keyWords"));
   		$this->assign('areaList',$areaList);
        $this->display("default/shop_street");
	}
	
	/**
     * 获取县区内的商铺
     */
	public function getDistrictsShops(){
   		$mshops = D('Home/Shops');
   		$obj["areaId3"] = (int)I("areaId3");
   		$obj["shopName"] = WSTAddslashes(I("shopName"));
   		$obj["deliveryStartMoney"] = (float)I("deliveryStartMoney");
   		$obj["deliveryMoney"] = (float)I("deliveryMoney");
   		$obj["shopAtive"] = (int)I("shopAtive");
   		cookie("bstreesAreaId3",$obj["areaId3"]);
   		
   		$dsplist = $mshops->getDistrictsShops($obj);
   		$this->ajaxReturn($dsplist);
	}
	
	/**
     * 获取社区内的商铺
     */
	public function getShopByCommunitys(){
		
   		$mshops = D('Home/Shops');
   		$obj["communityId"] = (int)I("communityId");
   		$obj["areaId3"] = (int)I("areaId3");
   		$obj["shopName"] = WSTAddslashes(I("shopName"));
   		$obj["deliveryStartMoney"] = (float)I("deliveryStartMoney");
   		$obj["deliveryMoney"] = (float)I("deliveryMoney");
   		$obj["shopAtive"] = (int)I("shopAtive",-1);
   		$ctplist = $mshops->getShopByCommunitys($obj);
   		$pages = $rslist["pages"];

   		$this->assign('ctplist',$pages);
       	$this->ajaxReturn($ctplist);
       	
	}
	
    /**
     * 跳到教师端登录页面
     */
	public function login(){
		$USER = session('WST_USER');
		if(!empty($USER) && $USER['userType']==1){
			$this->redirect("Shops/index");
		}else{
            $this->display("default/shop_login");
		}
	}
	
	/**
	 * 教师端登录验证
	 */
	public function checkLogin(){
		$rs = array('status'=>-2);
	    $rs["status"]= 1;
		if(!$this->checkVerify("4") && ($GLOBALS['CONFIG']["captcha_model"]["valueRange"]!="" && strpos($GLOBALS['CONFIG']["captcha_model"]["valueRange"],"3")>=0)){			
			$rs["status"]= -2;//验证码错误
		}else{
			$m = D('Home/Shops');
	   		$rs = $m->login();
	   		if($rs['status']==1){
	    		session('WST_USER',$rs['shop']);
	    		unset($rs['shop']);
	    	}
		}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 退出
	 */
	public function logout(){
		session('WST_USER',null);
		echo "1";
	}
	/**
	 * 跳到教师端中心页面
	 */
	public function index(){
		$this->isShopLogin();
		$spm = D('Home/Shops');
		$data['shop'] = $spm->loadShopInfo(session('WST_USER.userId'));
	    //	var_dump($data['shop']);
		//exit;
		$obj["shopId"] = $data['shop']['shopId'];
		$details = $spm->getShopDetails($obj);
		$data['details'] = $details;
		//var_dump($data);
		//exit;
		$this->assign('shopInfo',$data);
		
		$this->display("default/shops/index");
	}
	/**
	 * 编辑教师端资料
	 */
	public function toEdit(){
		$m = D('Home/Shops');
		$USER = session('WST_USER');
		$shop = $m->get((int)$USER['shopId']);
		if($shop["shopStatus"]!=-1){
			$this->isShopLogin();
		}
		
		
		if($_POST){
			//var_dump($_POST);
			//exit;
			$m = D('Home/Shops');
			$res=$m->edit($USER['shopId']);
			 if($res){ 
			 	
			 	$this->success('编辑教师成功',U('Shops/index'));
			//$this->redirect();
			 }
			
			
		}else{
		//获取银行列表
		//$m = D('Admin/Banks');
		//$this->assign('bankList',$m->queryByList(0));
		//获取课程信息
		//dump($shop);
		//$this->assign('object',$shop);
		//$this->assign("umark","toEdit");
		$this->redirect("default/shops/index");
		}
		
	}
	
	
	//教师段端订单
	public function  order(){
		
		
		$this->display("default/shops/order");
		
	}
	
	
	//教师段端课程
	public function course(){
		$this->isShopLogin();
		$USER = session('WST_USER');
		$shopCourse=D('Home/course');
		
		$page = $shopCourse->queryOnSaleByPage($USER['shopId']);
    	$pager = new \Think\Page($page['total'],$page['pageSize']);
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
		$this->display("default/shops/course");
	
	}
	
	

	//教师段端课程
	public function follow(){
	
	
		$this->display("default/shops/follow");
	
	}
	
	
	
	
	
	/**
	 * 设置教师端资料
	 */
	public function toShopCfg(){
		$this->isShopLogin();
        $USER = session('WST_USER');
		//获取课程信息
		$m = D('Home/Shops');
		$this->assign('object',$m->getShopCfg((int)$USER['shopId']));
		$this->assign("umark","setShop");
		$this->display("default/shops/cfg_shop");
	}
	/**
	 * 查询店铺名称是否存在
	 */
	public function checkShopName(){
		$m = D('Home/Shops');
		$rs = $m->checkShopName(I('shopName'),(int)I('id'));
		echo json_encode($rs);
	}
	/**
	 * 新增/修改操作
	 */
	public function editShopCfg(){
		$this->isShopLogin();
		$USER = session('WST_USER');
		$m = D('Home/Shops');
    	$rs = array('status'=>-1);
    	if($USER['shopId']>0){
    		$rs = $m->editShopCfg((int)$USER['shopId']);
    	}
    	$this->ajaxReturn($rs);
	}
	
   /**
	* 新增/修改操作
	*/
	public function edit(){
		$this->isShopLogin();
		$USER = session('WST_USER');
		$m = D('Home/Shops');
    	$rs = array('status'=>-1);
    	if($USER['shopId']>0){
    		$rs = $m->edit((int)$USER['shopId']);
    	}
    	$this->ajaxReturn($rs);
	}
	
   /**
    * 跳到修改用户密码
    */
	public function toEditPass(){
		$this->isShopLogin();
		$this->assign("umark","toEditPass");
        $this->display("default/shops/edit_pass");
	}
	
	/**
	 * 申请开店
	 */
	public function toOpenShopByUser(){
		$this->isUserLogin();
		$USER = session('WST_USER');
		if(!empty($USER) && $USER['userType']==0){
			//获取用户申请状态
			$m = D('Home/Shops');
			$shop = $m->checkOpenShopStatus((int)$USER['userId']);
			
			if(empty($shop)){
				//获取课程分类信息
				$m = D('Home/GoodsCats');
				$this->assign('goodsCatsList',$m->queryByList());
				//获取地区信息
				$m = D('Home/Areas');
				$this->assign('areaList',$m->getProvinceList());
				//获取所在城市信息
		        $cityId = $this->getDefaultCity();
		        $area = $m->getArea($cityId);
		        $this->assign('area',$area);
				//获取银行列表
				$m = D('Home/Banks');
				$this->assign('bankList',$m->queryByList(0));
				$object = $m->getModel();
				$object['areaId1'] = $area['parentId'];
				$object['areaId2'] = $area['areaId'];
				$this->assign('object',$object);
				$this->display("default/users/open_shop");
			}else{
				if($shop["shopStatus"]==1){
					$shops = $m->loadShopInfo((int)$USER['userId']);
					$USER = array_merge($USER,$shops);
					session('WST_USER',$USER);
					$this->assign('msg','您的申请已通过，请刷新页面后点击右上角的"卖家中心"进入店铺界面.');
					$this->display("default/users/user_msg");
				}else{
					if($shop["shopStatus"]==-1){
						$this->assign('msg','您的申请审核不通过【原因：'.$shop["statusRemarks"].'】,请<a style="color:blue;" href="'.U('Home/Shops/toEditShopByUser').'"> 点击这里 </a>进行修改！');
					}else{
						$this->assign('msg','您的申请正在审核中...');
					}
					$this->display("default/users/user_msg");
				}
			}
		}else{
			$this->redirect("Shops/index");
		}
	}
	
	/**
	 * 申请开店
	 */
	public function toEditShopByUser(){
		$this->isUserLogin();
		$USER = session('WST_USER');
		if(!empty($USER) && $USER['userType']==0){
			//获取用户申请状态
			$sm = D('Home/Shops');
			$shop = $sm->checkOpenShopStatus((int)$USER['userId']);
				
			if($shop["shopStatus"]==-1){
				//获取课程分类信息
				$m = D('Home/GoodsCats');
				$this->assign('goodsCatsList',$m->queryByList());
				//获取地区信息
				$m = D('Home/Areas');
				$this->assign('areaList',$m->getProvinceList());
				//获取所在城市信息
				$cityId = $this->getDefaultCity();
				//$area = $m->getArea($cityId);
				//$this->assign('area',$area);
				//获取银行列表
				$m = D('Home/Banks');
				$this->assign('bankList',$m->queryByList(0));
				//$object = $m->getModel();
				$object = $sm->getShopByUser((int)$USER['userId']);

				$this->assign('object',$object);
				$this->display("default/users/open_shop");
			}
		}else{
			$this->redirect("Shops/index");
		}
	}
	
	/**
	 * 会员提交开店申请
	 */
	public function openShopByUser(){
		$this->isUserLogin();
		$rs = array('status'=>-1);
		if($GLOBALS['CONFIG']['phoneVerfy']==1){
			$verify = session('VerifyCode_userPhone');
			$startTime = (int)session('VerifyCode_userPhone_Time');
			$mobileCode = I("mobileCode");
			if((time()-$startTime)>120){
				 $rs['msg'] = '验证码已失效!';
			}
			if($mobileCode=="" || $verify != $mobileCode){
				$rs['msg'] = '验证码错误!';
			}
    	}else{
	    	if(!$this->checkVerify("1")){			
				$rs['msg'] = '验证码错误!';
			}
    	}
    	if($rs['msg']==''){
			$USER = session('WST_USER');
			$m = D('Home/Shops');
	    	$userId = (int)$USER['userId'];
	    	$shop = $m->getShopByUser($userId);
	    	if($shop['shopId']>0){
	    		
	    		$rs = $m->edit((int)$shop['shopId'],true);
	    	}else{
			 	//如果用户没注册则先建立账号
				if($userId>0){
			   	    $rs = $m->addByUser($userId);
			    	if($rs['status']>0)$USER['shopStatus'] = 0;
				}
	    	}
    	}
    	$this->ajaxReturn($rs);
	}
	
	
	/**
	 * 游客跳到开店申请
	 */
    public function toOpenShop(){
    	//获取课程分类信息
		$m = D('Home/GoodsCats');
		$this->assign('goodsCatsList',$m->queryByList());
		//获取省份信息
		$m = D('Home/Areas');
		$this->assign('areaList',$m->getProvinceList());
		//获取所在城市信息
		$cityId = $this->getDefaultCity();
		$area = $m->getArea($cityId);
		$this->assign('area',$area);
		//获取银行列表
		$m = D('Home/Banks');
		$this->assign('bankList',$m->queryByList(0));
		$object = $m->getModel();
		$this->assign('object',$object);
		$this->display("default/open_shop");

	}
	
    /**
	 * 游客提交开店申请
	 */
	public function openShop(){
		$m = D('Home/Shops');
    	$rs = array('status'=>-1);
    	if($GLOBALS['CONFIG']['phoneVerfy']==1){
	    	$verify = session('VerifyCode_userPhone');
			$startTime = (int)session('VerifyCode_userPhone_Time');
			$mobileCode = I("mobileCode");
			if((time()-$startTime)>120){
			    $rs['msg'] = '验证码已失效!';
		    }
			if($mobileCode=="" || $verify != $mobileCode){
				$rs['msg'] = '验证码错误!';
			}
    	}else{
	    	if(!$this->checkVerify("1")){			
				$rs['msg'] = '验证码错误!';
			}
    	}
    	if($rs['msg']==''){
			$rs = $m->addByVisitor();
			$m = D('Home/Users');
			$user = $m->get($rs['userId']);
			if(!empty($user))session('WST_USER',$user);
    	}
    	$this->ajaxReturn($rs);
	}

	/**
	 * 获取店铺搜索提示列表
	 */
	public function getKeyList(){
		$m = D('Home/Shops');
		$areaId2 = $this->getDefaultCity();
		$rs = $m->getKeyList($areaId2);
		$this->ajaxReturn($rs);
	}
	
	
}