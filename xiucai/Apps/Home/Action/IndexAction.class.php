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
   		//首页分级课程一
   		$jArr=array('courseCatId1'=>15,'courseFlag'=>1,'courseStatus'=>1);
   		$courseIsBest1 = $course->where($jArr)->order("createTime desc")->limit(8)->select();
   		$this->assign('courseIsBest1',$courseIsBest1);
        //首页分级课程二
        $jArr = array('courseCatId1' => 12 , 'courseFlag' => 1 , 'courseStatus' => 1);
        $courseIsBest2 = $course->where($jArr)->order("createTime desc")->limit(8)->select();
        $this->assign('courseIsBest2' , $courseIsBest2);
        //首页分级课程三
        $jArr = array('courseCatId1' => 13 , 'courseFlag' => 1 , 'courseStatus' => 1);
        $courseIsBest3 = $course->where($jArr)->order("createTime desc")->limit(8)->select();
        $this->assign('courseIsBest3' , $courseIsBest3);
   		
   		//首页Banner广告位
   		$adArr=array('adPositionId'=>-1);
   		$aArrIndex=$ads->where($adArr)->select();
		if($aArrIndex){
   		$this->assign('aArrIndex',$aArrIndex);
   		}
        //首页推荐广告位
        $adArr = array('adPositionId' => -2);
        $tArrIndex = $ads->where($adArr)->select();
        if ($tArrIndex) {
            $this->assign('tArrIndex' , $tArrIndex);
        }
		$adArr = array('adPositionId' => -3);
        $tArrIndex1 = $ads->where($adArr)->select();
        if ($tArrIndex1) {
            $this->assign('tArrIndex1' , $tArrIndex1);
        }
		$adArr = array('adPositionId' => -4);
        $tArrIndex2 = $ads->where($adArr)->select();
        if ($tArrIndex2) {
            $this->assign('tArrIndex2' , $tArrIndex2);
        }
		$adArr = array('adPositionId' => -5);
        $tArrIndex3 = $ads->where($adArr)->select();
        if ($tArrIndex3) {
            $this->assign('tArrIndex3' , $tArrIndex3);
        }
   		//首页最新课程
   		$nArr=array('isAdminNew'=>1,'courseFlag'=>1,'isSale'=>1,'courseStatus'=>1);
   		$newCourse=$course->where($nArr)->limit(4)->select();
		if($newCourse){
   		$this->assign('newCourse',$newCourse);
   		}
		
		//首页最惠课程
   		$dArr=array('isAdminDiscount'=>1,'courseFlag'=>1,'isSale'=>1,'courseStatus'=>1);
   		$discountCourse=$course->where($dArr)->limit(4)->select();
		if($discountCourse){
			$this->assign('discountCourse',$discountCourse);
			}
   		
   		
   	
   		
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