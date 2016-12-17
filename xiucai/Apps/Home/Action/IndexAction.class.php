<?php
namespace Home\Action;
/**
 * ================================
 * 首页控制器
 */
class IndexAction extends BaseAction {
	/**
	 * 获取首页信息
	 * 
	 */
    public function index(){
    	//var_dump($GLOBALS['CONFIG']['captcha_model']);//0,1,2,3,4,5" 
   		$ads = D('Home/Ads');
   	
   		//获取分类
		$gcm = D('Home/GoodsCats');
		$catList = $gcm->getGoodsCatsAndGoodsForIndex($areaId2);
		$this->assign('catList',$catList);
   	
   		//首页推荐课程
   		$course=D('Home/Course');
   		$cArr=array('courseFlag'=>1,'isAdminRecom'=>1,'isSale'=>1);
   		$courseIndex=$course->where($cArr)->limit(4)->select();
   		$this->assign('courseIndex',$courseIndex);
   		//首页精品课程
   		$jArr=array('isAdminBest'=>1,'courseFlag'=>1,'isSale'=>1,'courseStatus'=>1);
   		$courseIsBest=$course->where($jArr)->limit(18)->select();
   		$this->assign('courseIsBest',$courseIsBest);
   		
   		//首页Banner广告位
   		$adArr=array('adPositionId'=>-1);
   		$aArrIndex=$ads->where($adArr)->select();
   		$this->assign('aArrIndex',$aArrIndex);
   		
   		//首页最新课程
   		$nArr=array('isNew'=>1,'courseFlag'=>1,'isSale'=>1,'courseStatus'=>1);
   		$newCourse=$course->where($nArr)->select();
   		$this->assign('newCourse',$newCourse);
   		
   	
   		
   		$this->display("default/index");
    }
    /**
     * 广告记数
     */
    public function access(){
    	$ads = D('Home/Ads');
    	$ads->statistics((int)I('id'));
    }
    /**
     * 切换城市
     */
    public function changeCity(){
    	$m = D('Home/Areas');
    	$areaId2 = $this->getDefaultCity();
    	$provinceList = $m->getProvinceList();
    	$cityList = $m->getCityGroupByKey();
    	$area = $m->getArea($areaId2);
    	$this->assign('provinceList',$provinceList);
    	$this->assign('cityList',$cityList);
    	$this->assign('area',$area);
    	$this->assign('areaId2',$areaId2);
    	$this->display("default/change_city");
    }
    /**
     * 跳到用户注册协议
     */
    public function toUserProtocol(){
    	$this->display("default/user_protocol");
    }
    
    /**
     * 修改切换城市ID
     */
    public function reChangeCity(){
    	$this->getDefaultCity();
    }
    
}