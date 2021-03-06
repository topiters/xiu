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
	
	
	public function __construct(){
		parent::__construct();
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
		
		
	}
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
			if($_FILES){
				
				$_POST['shopImg']=$this->uploadShopPic();
				
			}
			//var_dump($_POST['shopImg']);
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
		$this->isShopLogin();
		
		
		
		$order = D('Home/Orders');
		
		
		$USER = session('WST_USER');
		//$obj['userId']=$USER['userId'];
		$obj['shopId']=$USER['shopId'];
		$shoporderlist=$order->queryByShopOrder($obj);
		//var_dump($shoporderlist);
	//exit;
		$pager = new \Think\Page($shoporderlist['total'],$shoporderlist['pageSize']);
		$page['pager'] = $pager->show();
		$this->assign('Page',$page);
		//$userPwd="123456";
		//$rs['loginSecret']="3878";
		//$v=md5($userPwd.$rs['loginSecret']);
		//echo"<pre>";
		
		//echo"</pre>";
		$this->assign("shoporderlist",$shoporderlist);
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
	//添加课程
	public function addcourse(){
//	    dump($_POST);die;
		$USER = session('WST_USER');
		//查询教师状态
		$shopId = (int)session('WST_USER.shopId');
		$sql = "select shopStatus from __PREFIX__shops where shopFlag = 1 and shopId=".$shopId;
		$shopStatus = D('Shops')->query($sql);
		if(empty($shopStatus)){
			$rd['status'] = -2;
			return $rd;
		}
		$mshopcats=D('courseCats');
		$data=array();
		if($_POST){
			$data['courseName']=$_POST['courseName'];
			//当前分类
			$cate=$_POST['catId'];
			$shopcats=$mshopcats->where(array('catId'=>$cate))->find();
			// 获取二级分类
			if($shopcats['parentId']!=0){
				$data['courseCatId2']=$cate;
				$data['courseCatId1']=$shopcats['parentId'];
				//获取一级分类
			$shopcats0=$mshopcats->where(array('catId'=>$shopcats['parentId']))->find();
				
				
				if($shopcats0['parentId']!=0){
					$data['courseCatId1']=$shopcats0['parentId'];
					$data['courseCatId2']=$shopcats['parentId'];
					$data['courseCatId3']=$cate;
				}
				
				
				
				
				
				
			}else{
				
				$data['courseCatId1']=$cate;
				
				
			}
			
			$data['courseName']=$_POST['courseName'];
			
			if(empty($data['courseName'])){
				
			  $this->error('课程名称不能为空');
			}
			$data['courseTime']=$_POST['courseTime'];
			$data['is_live']=(int)$_POST['is_live'];
			$data['is_free']=(int)$_POST['is_free'];
			if($data['is_live']==2){//直播
			$data['liveStartTime']=$_POST['liveStartTime'];
			$data['liveEndTime']=$_POST['liveEndTime'];
			$data['vid']=$_POST['vid'];//直播频道号

			}
			//var_dump($data);
			//exit;
				$data['shopPrice']=$_POST['shopPrice'];
				$data['marketPrice']=$_POST['marketPrice'];
			
			//$data['shopId']= (int)session('WST_USER.shopId');
			$data['isBest']= ((int)I('isBest')==1)?1:0;
			$data['isRecomm'] = ((int)I('isRecomm')==1)?1:0;
			$data['isNew']= ((int)I('isNew')==1)?1:0;
			$data['isHot']= ((int)I('isHot')==1)?1:0;
			$data['createTime'] = date('Y-m-d H:i:s');
			$data['courseSn']=time().rand(1000,9999);
			$data['courseDifficulty']=$_POST['is_difficulty'];
			$data['courseIntro']=$_POST['courseIntro'];
			if(empty($data['courseIntro'])){
				$this->error('请输入课程简介');
				
			}
			$data['courseFor']=$_POST['courseFor'];
			$data['courseDesc']=$_POST['courseDetails'];
		    $data['courseThums']=$this->uploadShopPic();
			$data['shopId']=$USER['shopId'];
			$data['videoPath']=$_POST['videodata'];
			
			if($data['is_live']==1){
			if(empty($data['videoPath'])){
				$this->error('请上传课程视屏');
			}
			
			}
			//var_dump($data);
			//exit;
          $res=D('Course')->add($data);
			
			if( $res){

				$this->success('添加成功',U('Home/shops/course'));
				
			}else{
				
				$this->error('课程添加失败');
			}
		}else{
			//$courseCats=WSTGoodsCats();
		//	var_dump($courseCats);
			//exit;
			$this->assign('nowtime',time());
			$this->display("default/shops/addcourse");
		}
		
		
		
	}

	//教师段端课程
	public function follow(){
	
		$this->isShopLogin();
		$shopId = (int)session('WST_USER.shopId');
		$shopFollow = D('Home/Shops');
		$followslist=$shopFollow->getShopsfollow($shopId);
	   $this->assign('followslist',$followslist);
	   $this->display("default/shops/follow");
	
	}
	
	
	//教师段端问答
	public function ask(){

		$this->isShopLogin();
		$type = I('type');
		$shopId = (int)session('WST_USER.shopId');
		$shopReply = D('Home/Shops');
		$replylist=$shopReply->getShopsreply($shopId,$type);
		//$replylist['totalPage']=100;
		$this->assign('replylist',$replylist);
//		dump($replylist['root']);die;
		$this->display("default/shops/ask");
	
	}

    /**
     * 处理回答
     */
    public function answer() {
        if ($_POST['qId'] && $_POST['shopId'] && $_POST['content']){
            $_POST['ctime'] = time();
            $re = D('answers')->add($_POST);
            if ($re) {
                D('shops')->query("update __PREFIX__shops set shopAnswers = shopAnswers + 1 where shopId = {$_POST['shopId']}");
                D('questions')->query("update __PREFIX__questions set is_answered = 1 where id = {$_POST['qId']}");
                echo 1;
            }
        }
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
	
	public function uploadShopPic(){
		$config = array(
				'maxSize'       =>  0, //上传的文件大小限制 (0-不做限制)
				'exts'          =>  array('jpg','png','gif','jpeg'), //允许上传的文件后缀
				'rootPath'      =>  './Upload/', //保存根路径
				'driver'        =>  'LOCAL', // 文件上传驱动
				'subName'       =>  array('date', 'Y-m'),
				'savePath'      =>  I('dir','uploads')."/"
		);
		$dirs = explode(",",C("WST_UPLOAD_DIR"));
		if(!in_array(I('dir','uploads'), $dirs)){
			echo '非法文件目录！';
			return false;
		}
	
		$upload = new \Think\Upload($config);
		$rs = $upload->upload($_FILES);
		//var_dump($rs);
		//exit;
		$Filedata = key($_FILES);
		if(!$rs){
			$this->error($upload->getError());
		}else{
			/*$images = new \Think\Image();
			$images->open('./Upload/'.$rs[$Filedata]['savepath'].$rs[$Filedata]['savename']);
			$newsavename = str_replace('.','_thumb.',$rs[$Filedata]['savename']);
			$vv = $images->thumb(I('width',300), I('height',300))->save('./Upload/'.$rs[$Filedata]['savepath'].$newsavename);
			if(C('WST_M_IMG_SUFFIX')!=''){
				$msuffix = C('WST_M_IMG_SUFFIX');
				$mnewsavename = str_replace('.',$msuffix.'.',$rs[$Filedata]['savename']);
				$mnewsavename_thmb = str_replace('.',"_thumb".$msuffix.'.',$rs[$Filedata]['savename']);
				$images->open('./Upload/'.$rs[$Filedata]['savepath'].$rs[$Filedata]['savename']);
				$images->thumb(I('width',700), I('height',700))->save('./Upload/'.$rs[$Filedata]['savepath'].$mnewsavename);
				$images->thumb(I('width',250), I('height',250))->save('./Upload/'.$rs[$Filedata]['savepath'].$mnewsavename_thmb);
			}
			*/
			$rs[$Filedata]['savepath'] = "Upload/".$rs[$Filedata]['savepath'].$rs[$Filedata]['savename'];
			//$rs[$Filedata]['savethumbname'] = $newsavename;
			//$rs['status'] = 1;
				
			return $rs[$Filedata]['savepath'];
	
		}
	}
	
	//删除课程
	function delCourse(){
		
		
		$m = M('course');
		$shopId = (int)session('WST_USER.shopId');
		$data = array();
		$data["courseFlag"] = -1;
		$rs = $m->where("shopId=".$shopId." and courseId=".I('courseId'))->delete();
	//	var_dump($m->getLastSql());
		if(false !== $rs){
			$rd['status']= 1;
		}
		$this->AjaxReturn($rd['status']);
		
		
		
	}
	
	
	
	
	
	
}