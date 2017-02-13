<?php
namespace Home\Action;
/**
*  课程文件
* ==============================================
* 版权所有 2015-2016 http://www.chunni168.com
* ----------------------------------------------
* 这不是一个自由软件，未经授权不许任何使用和传播。
* ==============================================
* @date: 2016-11-21
* @author: top_iter、lnrp
* @email:2504585798@qq.com
* @version:
**/
class CourseAction extends BaseAction {
	
	
	/**
	 * 课程列表
	 */
	
	public function  index(){
		$mcourses = D('Home/Course');
		//$mareas = D('Home/Areas');
		$mcoursesCat= D('Home/CourseCats');
		//$mcommunitys = D('Home/Communitys');
		$this->assign('msort',(int)I("msort",0));
		$this->assign('mark',(int)I("mark",0));
		$is_new=(int)I("is_new");
		if($is_new){
			$this->assign('is_new',$is_new);
		}
		$is_hot=(int)I("is_hot");
		if($is_hot){
			$this->assign('is_hot',$is_hot);
		}
		
		//是否直播
	/* 	$is_live=(int)I("is_live",0);
		if($is_live){
			$this->assign('is_live',$is_live);
		} */
		    $is_free=(int)I("is_free",0);
		if($is_free){
			$this->assign('is_free',$is_free);
		}
		$c1Id = (int)I("c1Id");//如果没有分类默认热门课程分类
		$c2=(int)I("c2Id");
		if($c2){
		$this->assign('c2Id',$c2);
		}
		if($c1Id){
	      $this->assign('c1Id',$c1Id);
	     // var_dump($c1Id);
		};
		$rslist = $mcourses->getCourseList();
//		dump($rslist);die;
		//$brands = $rslist["brands"];
		$pages = $rslist["pages"];
		
		//var_dump($pages);
		//$coursesNav = $rslist["coursesNav"];
		//$pages['totalPage']=10;
		$this->assign('pages',$pages);
	    $c1=$c1Id ?$c1Id :11;
		if($c1){
			
		$c2cat=$mcoursesCat->queryByList($c1);
		
		
		
		$this->assign("c2cat",$c2cat);
		
		//var_dump($c2cat);
		//exit;
		}
		
		$this->display('default/courses_list');
		
		
		
		
	}
	//定制课程
   public  function ding(){
	   $course=D('Home/Course');
   		$cArr=array('courseFlag'=>1,'isAdminRecom'=>1,'isSale'=>1);
   		$courseIndex=$course->where($cArr)->limit(6)->select();
   		$this->assign('courseIndex',$courseIndex);
	   
	   	$this->display('default/course_ding');
   }
  
  //提交定制课                                                                                                                                    
  public function mylikecourse(){
	$courseName=I('courseName');
	$coursecontent=I('coursecontent');
	$phone=I('phone');
	$email=I('email');
	if($phone){
		$data['courseName']=$courseName;
		$data['coursecontent']=$coursecontent;
		$data['phone']=$phone;
		$data['email']=$email;
		$data['times']=time();
		$res=D('course_ding')->add($data);
		if($res){
			$msg=1;
			$this->ajaxReturn($msg);
			
		}
		
		
		
		
		
	}
	
	
	
	 
	  
  }
  
  
	/**
	 * 查询商品详情
	 * 
	 */
	public function getCourseDetails(){

		$courses = D('Home/Course');
		$kcode = I("kcode");
		$scrictCode = md5(base64_encode("wstmall".date("Y-m-d")));
		
		//查询商品详情		
		$coursesId = (int)I("courseId");
		$this->assign('courseId',$coursesId);
		$obj["courseId"] = $coursesId;	
		
		//$packages = $courses->getCoursePackages($coursesId,1);
		//->assign('packages',$packages);
		
		$coursesDetails = $courses->getCourseDetails($obj);
		
		//var_dump($coursesDetails);
		//exit;
		if($kcode==$scrictCode || ($coursesDetails["isSale"]==1 && $coursesDetails["courseStatus"]==1)){
			if($kcode==$scrictCode){//来自后台管理员
				$this->assign('comefrom',1);
			}
			
			$shopServiceStatus = 1;
			if($coursesDetails["shopAtive"]==0){
				$shopServiceStatus = 0;
			}
		//	$coursesDetails["serviceEndTime"] = str_replace('.5',':30',$coursesDetails["serviceEndTime"]);
		//	$coursesDetails["serviceEndTime"] = str_replace('.0',':00',$coursesDetails["serviceEndTime"]);
		//	$coursesDetails["serviceStartTime"] = str_replace('.5',':30',$coursesDetails["serviceStartTime"]);
		//	$coursesDetails["serviceStartTime"] = str_replace('.0',':00',$coursesDetails["serviceStartTime"]);
			$coursesDetails["shopServiceStatus"] = $shopServiceStatus;
			$coursesDetails['coursesDesc'] = htmlspecialchars_decode($coursesDetails['coursesDesc']);
			
			
			//$areas = D('Home/Areas');
			$shopId = intval($coursesDetails["shopId"]);
			$obj["shopId"] = $shopId;
			//$obj["areaId2"] = $this->getDefaultCity();
			$obj["attrCatId"] = $coursesDetails['attrCatId'];
			$shops = D('Home/Shops');
			$shopScores = $shops->getShopScores($obj);
			$this->assign("shopScores",$shopScores);
			
			//$shopCity = $areas->getDistrictsByShop($obj);
			//$this->assign("shopCity",$shopCity[0]);
			
			//$shopCommunitys = $areas->getShopCommunitys($obj);
			//$this->assign("shopCommunitys",json_encode($shopCommunitys));
			
			$this->assign("coursesImgs",$courses->getCourseImgs());
			// 相关课程
			$this->assign("relatedCourse",$nv=$courses->getRelatedCoursed($coursesDetails['courseCatId2']));
			$this->assign("coursesNav",$courses->getCourseNav());
		//var_dump($nv);
			//exit;
			$this->assign("coursesAttrs",$atrr=$courses->getAttrs($obj));
			//var_dump($atrr);
			//exit;
			$this->assign("coursesDetails",$coursesDetails);
			//var_dump($coursesDetails);
			//exit;
			$viewCourse = cookie("viewCourse");
			if(!in_array($coursesId,$viewCourse)){
				$viewCourse[] = $coursesId;
			}
			if(!empty($viewCourse)){
				cookie("viewCourse",$viewCourse,25920000);
			}
			//获取关注信息
			$m = D('Home/Favorites');
			$this->assign("favoriteCourseId",$m->checkFavorite($coursesId,0));
			$m = D('Home/Favorites');
			$this->assign("favoriteShopId",$m->checkFavorite($shopId,1));
			//客户端二维码
			$this->assign("qrcode",base64_encode("{type:'courses',content:'".$coursesId."',key:'wstmall'}"));
			$user = session('WST_USER');
			$isVip = D('users')->field('userId,vip')->where("userId={$user['userId']}")->find();
			$this->assign('vip',$isVip['vip']);
			if ($isVip['vip'] < 1){
                $buy = D('order_course')->where("buyerName={$user['loginName']} and courseId = $coursesId")->find();
                if ($buy){
                    $this->assign('buy' , 1);
                } else {
                    $this->assign('buy' , 0);
                }
            }
			$this->display('default/courses_details');
		}else{
			//'default/courses_notexist'
			$this->error('该课程暂时不存在',U('course/index'));
		}

	}
	
	/**
	 * 获取商品库存
	 * 
	 */
	public function getCourseStock(){
		$data = array();
		$data['coursesId'] = (int)I('coursesId');
		$data['isBook'] = (int)I('isBook');
		$data['coursesAttrId'] = (int)I('coursesAttrId');
		$courses = D('Home/Course');
		$coursesStock = $courses->getCourseStock($data);
		echo json_encode($coursesStock);
		
	}
	
	/**
	 * 获取服务社区
	 * 
	 */
	public function getServiceCommunitys(){
		
		$areas = D('Home/Areas');
		$serviceCommunitys = $areas->getShopCommunitys();
		echo json_encode($serviceCommunitys);
	}
	
   /**
	* 分页查询-出售中的商品
	*/
	public function queryOnSaleByPage(){
		$this->isShopLogin();
		$USER = session('WST_USER');
		//获取商家商品分类
		//$m = D('Home/ShopsCats');
		//$this->assign('shopCatsList',$m->queryByList($USER['shopId'],0));
		$m = D('Home/Course');
    	$page = $m->queryOnSaleByPage($USER['shopId']);
    	$pager = new \Think\Page($page['total'],$page['pageSize']);
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign("umark","queryOnSaleByPage");
    	$this->assign("shopCatId2",I('shopCatId2'));
    	$this->assign("shopCatId1",I('shopCatId1'));
    	$this->assign("coursesName",I('coursesName'));
        $this->display("default/shops/courses/list_onsale");
	}
   /**
	* 分页查询-仓库中的商品
	*/
	public function queryUnSaleByPage(){
		$this->isShopLogin();
		$USER = session('WST_USER');
		//获取商家商品分类
		$m = D('Home/ShopsCats');
		$this->assign('shopCatsList',$m->queryByList($USER['shopId'],0));
		$m = D('Home/Course');
    	$page = $m->queryUnSaleByPage($USER['shopId']);
    	$pager = new \Think\Page($page['total'],$page['pageSize']);
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign("umark","queryUnSaleByPage");
    	$this->assign("shopCatId2",I('shopCatId2'));
    	$this->assign("shopCatId1",I('shopCatId1'));
    	$this->assign("coursesName",I('coursesName'));
        $this->display("default/shops/courses/list_unsale");
	}
   /**
	* 分页查询-在审核中的商品
	*/
	public function queryPenddingByPage(){
		$this->isShopLogin();
		$USER = session('WST_USER');
		//获取商家商品分类
		$m = D('Home/ShopsCats');
		$this->assign('shopCatsList',$m->queryByList($USER['shopId'],0));
		$m = D('Home/Course');
    	$page = $m->queryPenddingByPage($USER['shopId']);
    	$pager = new \Think\Page($page['total'],$page['pageSize']);
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign("umark","queryPenddingByPage");
    	$this->assign("shopCatId2",I('shopCatId2'));
    	$this->assign("shopCatId1",I('shopCatId1'));
    	$this->assign("coursesName",I('coursesName'));
        $this->display("default/shops/courses/list_pendding");
	}
	/**
	 * 跳到新增/编辑商品
	 */
    public function toEdit(){
		$this->isShopLogin();
		$USER = session('WST_USER');
		//获取商品分类信息
		$m = D('Home/CourseCats');
		$this->assign('coursesCatsList',$m->queryByList());
		$sm = D('Home/ShopsCats');
		$pkShopCats = $sm->getCatAndChild($USER['shopId']);
		$this->assign('pkShopCats',$pkShopCats);
		//获取商家商品分类
		$m = D('Home/ShopsCats');
		$this->assign('shopCatsList',$m->queryByList($USER['shopId'],0));
		//获取商品类型
		$m = D('Home/AttributeCats');
		$this->assign('attributeCatsCatsList',$m->queryByList());
		$m = D('Home/Course');
		$object = array();
		$coursesId = (int)I('id',0);
    	if($coursesId>0){
    		$object = $m->get();
    		$packages = $m->getCoursePackages($coursesId);
    		$this->assign('packages',$packages);
    	}else{
    		$object = $m->getModel();
    	}
    	$this->assign('object',$object);
    	$this->assign("umark",I('umark'));
        $this->display("default/shops/courses/edit");
	}
	/**
	 * 新增商品
	 */
	public function edit(){
		$this->isShopLogin();
		$m = D('Home/Course');
    	$rs = array();
    	if((int)I('id',0)>0){
    		$rs = $m->edit();
    	}else{
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除商品
	 */
	public function del(){
		$this->isShopLogin();
		$m = D('Home/Course');
		$rs = $m->del();
		$this->ajaxReturn($rs);
	}
	/**
	 * 批量设置商品状态
	 */
	public function coursesSet(){
		$this->isShopLogin();
		$m = D('Home/Course');
		$rs = $m->coursesSet();
		$this->ajaxReturn($rs);
	}
	/**
	 * 批量删除商品
	 */
	public function batchDel(){
		$this->isShopLogin();
		$m = D('Home/Course');
		$rs = $m->batchDel();
		$this->ajaxReturn($rs);
	}
	/**
	 * 修改商品上架/下架状态
	 */
	public function sale(){
		$this->isShopLogin();
		$m = D('Home/Course');
		$rs = $m->sale();
		$this->ajaxReturn($rs);
	}
	
	
	
	/**
	 * 核对商品信息
	 */
	public function checkCourseStock(){
	
		$m = D('Home/Cart');
		$catcourses = $m->checkCourseStock();
		$this->ajaxReturn($catcourses);
	
	}
	
	/**
	 * 获取验证码
	 */
	public function getCourseVerify(){
		$data = array();
		$data["status"] = 1;
		$verifyCode = md5(base64_encode("wstmall".date("Y-m-d")));
		$data["verifyCode"] = $verifyCode;
		$this->ajaxReturn($data);
	}
	
	/**
	 * 查询商品属性价格及库存
	 */
    public function getPriceAttrInfo(){
    	$courses = D('Home/Course');
		$rs = $courses->getPriceAttrInfo();
		$this->ajaxReturn($rs);
    }
	/**
	 * 修改商品库存
	 */
    public function editStock(){
    	$this->isShopLogin();
    	$m = D('Home/Course');
    	$rs = $m->editStock();
    	$this->ajaxReturn($rs);
    }
    
    /**
     * 修改商品库存,商品编号,价格
     */
    public function editCourseBase(){
    	$this->isShopLogin();
    	$m = D('Home/Course');
    	$rs = $m->editCourseBase();
    	$this->ajaxReturn($rs);
    }
    
    /**
     * 获取商品搜索提示列表
     */
    public function getKeyList(){
    	$m = D('Home/Course');
    	$areaId2 = $this->getDefaultCity();
    	$rs = $m->getKeyList($areaId2);
    	$this->ajaxReturn($rs);
    }
    
    /**
     * 修改 推荐/精品/新品/热销/上架
     */
    public function changSaleStatus(){
    	$this->isShopLogin();
    	$m = D('Home/Course');
    	$rs = $m->changSaleStatus();
    	$this->ajaxReturn($rs);
    }
    
    /**
     * 上传商品数据
     */
    public function importCourse(){
    	$this->isShopLogin();
    	$config = array(
		        'maxSize'       =>  0, //上传的文件大小限制 (0-不做限制)
		        'exts'          =>  array('xls','xlsx','xlsm'), //允许上传的文件后缀
		        'rootPath'      =>  './Upload/', //保存根路径
		        'driver'        =>  'LOCAL', // 文件上传驱动
		        'subName'       =>  array('date', 'Y-m'),
		        'savePath'      =>  I('dir','uploads')."/"
		);
		$upload = new \Think\Upload($config);
		$rs = $upload->upload($_FILES);
		$rv = array('status'=>-1);
		if(!$rs){
			$rv['msg'] = $upload->getError();
		}else{
			$m = D('Home/Course');
    	    $rv = $m->importCourse($rs);
		}
    	$this->ajaxReturn($rv);
    }
    
    public function getCourseByCat() {
    	$this->isShopLogin();
    	$m = D('Home/Course');
    	$rs = $m->getCourseByCat();
    	$this->ajaxReturn($rs);
    }
    
    public function getPackageCourse(){
    	$this->isShopLogin();
    	$m = D('Home/Course');
    	$rs = $m->getPackageCourse();
    	$this->ajaxReturn($rs);
    }
    
    public function editCoursePackages(){
    	$this->isShopLogin();
    	$m = D('Home/Course');
    	$rs = $m->editCoursePackages();
    	$this->ajaxReturn($rs);
    }
	
    
 
    //直接观看
    public  function  views(){
    	
    	$this->isUserLogin();
    	$userId = (int)session('WST_USER.userId');
    	$vid=I('vid');
    	$courseOne=D('course')->where(array('courseId'=>$vid))->find();
    	
    	$this->assign('courseOne',$courseOne);
    	
    	$this->display('default/users/views');
    	
    	
    	
    	
    }
    
    
    
    
    
    
}