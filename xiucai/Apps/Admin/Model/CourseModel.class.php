<?php
 namespace Admin\Model;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 商品服务类
 */
class CourseModel extends BaseModel {
    
	/**
	 * 获取商品信息
	 */
	 public function get(){
	 	$m = M('course');
	 	$id = (int)I('id',0);
		$course = $this->where("courseId=".$id)->find();
		//相册
		$m = M('course_gallerys');
		$course['gallery'] = $m->where('courseId='.$id)->select();
		//商城分类
		$sql = "select c1.catName courseName1,c2.catName courseName2,c3.catName courseName3
		        from __PREFIX__course_cats c3 , __PREFIX__course_cats c2,__PREFIX__course_cats c1
		        where c3.parentId=c2.catId and c2.parentId=c1.catId and c3.catId=".$course['courseCatId3'];
		$rs = $this->query($sql);
		$course['courseCats'] = $rs[0];
		//店铺分类
		$sql = "select c1.catName courseName1,c2.catName courseName2
		        from __PREFIX__shops_cats c2 ,__PREFIX__shops_cats c1
		        where c2.parentId=c1.catId and c2.catId=".$course['shopCatId2'];
		$rs = $this->query($sql);
		$course['shopCats'] = $rs[0];
		//属性
		if($course['attrCatId']>0){
			$sql = "select catName from __PREFIX__attribute_cats where catId=".$course['attrCatId'];
			$rs = $this->query($sql);
		    $course['attrCatName'] = $rs[0]['catName'];
		    
			//获取规格属性
			$sql = "select ga.attrVal,ga.attrPrice,ga.attrStock,a.attrId,a.attrName,a.isPriceAttr,ga.isRecomm
			            ,ga.isRecomm from __PREFIX__attributes a 
			            left join __PREFIX__course_attributes ga on ga.attrId=a.attrId and ga.courseId=".$id." where  
						a.attrFlag=1 and a.catId=".$course['attrCatId']." and a.shopId=".$course['shopId'];
			$attrRs = $this->query($sql);
			if(!empty($attrRs)){
				$priceAttr = array();
				$attrs = array();
				foreach ($attrRs as $key =>$v){
					if($v['isPriceAttr']==1){
						$course['priceAttrName'] = $v['attrName'];
						$priceAttr[] = $v;
					}else{
						$v['attrContent'] = $v['attrVal'];
						$attrs[] = $v;
					}
				}
				$course['priceAttrs'] = $priceAttr;
				$course['attrs'] = $attrs;
			}
		}
		return $course;
	 }
	 /**
	  * 分页列表[获取待审核列表]
	  */
     public function queryPenddingByPage(){
        $shopName = WSTAddslashes(I('shopName'));
     	$courseName = WSTAddslashes(I('courseName'));
     	$areaId1 = (int)I('areaId1',0);
     	$areaId2 = (int)I('areaId2',0);
     	$courseCatId1 = (int)I('courseCatId1',0);
     	$courseCatId2 = (int)I('courseCatId2',0);
     	$courseCatId3 = (int)I('courseCatId3',0);
	 	$sql = "select g.*,gc.catName,sc.catName shopCatName,p.shopName from __PREFIX__course g 
	 	      left join __PREFIX__course_cats gc on g.courseCatId3=gc.catId 
	 	      left join __PREFIX__shops_cats sc on sc.catId=g.shopCatId2,__PREFIX__shops p 
	 	      where courseStatus=0 and courseFlag=1 and p.shopId=g.shopId and g.isSale=1";
	 	if($areaId1>0)$sql.=" and p.areaId1=".$areaId1;
	 	if($areaId2>0)$sql.=" and p.areaId2=".$areaId2;
	 	if($courseCatId1>0)$sql.=" and g.courseCatId1=".$courseCatId1;
	 	if($courseCatId2>0)$sql.=" and g.courseCatId2=".$courseCatId2;
	 	if($courseCatId3>0)$sql.=" and g.courseCatId3=".$courseCatId3;
	 	if($shopName!='')$sql.=" and (p.shopName like '%".$shopName."%' or p.shopSn like '%".$shopName."%')";
	 	if($courseName!='')$sql.=" and (g.courseName like '%".$courseName."%' or g.courseSn like '%".$courseName."%')";
	 	$sql.="  order by courseId desc";
		return $this->pageQuery($sql);
	 }
	 /**
	  * 分页列表[获取已审核列表]
	  */
     public function queryByPage(){
        $shopName = WSTAddslashes(I('shopName'));
     	$courseName = WSTAddslashes(I('courseName'));
     	$areaId1 = (int)I('areaId1',0);
     	$areaId2 = (int)I('areaId2',0);
     	$courseCatId1 = (int)I('courseCatId1',0);
     	$courseCatId2 = (int)I('courseCatId2',0);
     	$courseCatId3 = (int)I('courseCatId3',0);
     	$isAdminBest = (int)I('isAdminBest',-1);
     	$isAdminRecom = (int)I('isAdminRecom',-1);
	 	$sql = "select g.*,gc.catName,sc.catName shopCatName,p.shopName from __PREFIX__course g 
	 	      left join __PREFIX__course_cats gc on g.courseCatId3=gc.catId 
	 	      left join __PREFIX__shops_cats sc on sc.catId=g.shopCatId2,__PREFIX__shops p 
	 	      where courseStatus=1 and courseFlag=1 and p.shopId=g.shopId and g.isSale=1";
	 	if($areaId1>0)$sql.=" and p.areaId1=".$areaId1;
	 	if($areaId2>0)$sql.=" and p.areaId2=".$areaId2;
	 	if($courseCatId1>0)$sql.=" and g.courseCatId1=".$courseCatId1;
	 	if($courseCatId2>0)$sql.=" and g.courseCatId2=".$courseCatId2;
	 	if($courseCatId3>0)$sql.=" and g.courseCatId3=".$courseCatId3;
	 	if($isAdminBest>=0)$sql.=" and g.isAdminBest=".$isAdminBest;
	 	if($isAdminRecom>=0)$sql.=" and g.isAdminRecom=".$isAdminRecom;
	 	if($shopName!='')$sql.=" and (p.shopName like '%".$shopName."%' or p.shopSn like '%".$shopName."%')";
	 	if($courseName!='')$sql.=" and (g.courseName like '%".$courseName."%' or g.courseSn like '%".$courseName."%')";
	 	$sql.="  order by courseId desc";   
		return $this->pageQuery($sql);
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
	     $sql = "select * from __PREFIX__course order by courseId desc";
		 return $this->find($sql);
	  }
	 /**
	  * 修改商品状态
	  */
	 public function changeCourseStatus(){
	 	$rd = array('status'=>-1);
	 	$ids = I('id',0);
	 	$ids = explode(',',$ids);
	 	foreach($ids as $k=>$id)
	 	{
		 	$this->courseStatus = (int)I('status',0);
			$rs = $this->where('courseId='.$id)->save();
			if(false !== $rs){
				$sql = "select courseName,userId from __PREFIX__course g,__PREFIX__shops s where g.shopId=s.shopId and g.courseId=".$id;
				$course = $this->query($sql);
				$msg = "";
				if(I('status',0)==0){
					$msg = "商品[".$course[0]['courseName']."]已被商城下架";
				}else{
					$msg = "商品[".$course[0]['courseName']."]已通过审核";
				}
				$yj_data = array(
					'msgType' => 0,
					'sendUserId' => session('WST_STAFF.staffId'),
					'receiveUserId' => $course[0]['userId'],
					'msgContent' => $msg,
					'createTime' => date('Y-m-d H:i:s'),
					'msgStatus' => 0,
					'msgFlag' => 1,
				);
				M('messages')->add($yj_data);
				$rd['status'] = 1;
	 		}
		}

		return $rd;
	 }
	 /**
	  * 获取待审核的商品数量
	  */
	 public function queryPenddingCourseNum(){
	 	$rd = array('status'=>-1);
	 	$sql="select count(*) counts from __PREFIX__course where courseStatus=0 and courseFlag=1 and isSale=1";
	 	$rs = $this->query($sql);
	 	$rd['num'] = $rs[0]['counts'];
	 	return $rd;
	 }
	 /**
	  * 批量修改精品状态
	  */
	 public function changeBestStatus(){
	 	$rd = array('status'=>-1);
	 	$id = I('id',0);
	 	$id = self::formatIn(",", $id);
	 	$this->isAdminBest = (int)I('status',0);
		$rs = $this->where('courseId in('.$id.")")->save();
		if(false !== $rs){
			$rd['status'] = 1;
		}
		return $rd;
	 }
     /**
	  * 批量修改推荐状态
	  */
	 public function changeRecomStatus(){
	 	$rd = array('status'=>-1);
	 	$id = I('id',0);
	 	$id = self::formatIn(",", $id);
	 	$this->isAdminRecom = (int)I('status',0);
		$rs = $this->where('courseId in('.$id.")")->save();
		if(false !== $rs){
			$rd['status'] = 1;
		}
		return $rd;
	 }
	 
	 
};
?>