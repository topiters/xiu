<?php
 namespace Admin\Model;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 文章服务类
 */
class OfflineModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function insert(){
	 	$rd = array('status'=>-1);
	 	$id = (int)I("id",0);
		$data = array();
		$data["catId"] = (int)I("catId");
		$data["offStartDate"] = I("offStartDate");
		$data["offEndDate"] = I("offEndDate");
		$data["offlineTitle"] = I("offlineTitle");
		$data["isShow"] = (int)I("isShow",0);
		$data["offFile"] = I("offFile");
		$data["offlineContent"] = I("offlineContent");
		$data["offlineKey"] = I("offlineKey");
		$data["offlinePrice"] = I("offlinePrice");
		$data["offlineTeacher"] = I("offlineTeacher");
		$data["offlineFor"] = I("offlineFor");
		$data["offlineTeacherIntro"] = I("offlineTeacherIntro");
		$data["offlineVipPrice"] = I("offlineVipPrice");
		//$data["offlineKey"] = I("offlineKey");
		$data["areaId1"] = (int)I("areaId1");
		$data["areaId2"] = (int)I("areaId2");
		//$data["areaId3"] = (int)I("areaId3");
		$data["staffId"] = (int)session('WST_STAFF.staffId');
		$data["createTime"] = date('Y-m-d H:i:s');
	    if($this->checkEmpty($data,true)){
			$rs = $this->add($data);
		    if(false !== $rs){
				$rd['status']= 1;
			}
		}
		return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit(){
	 	$rd = array('status'=>-1);
	 	$id = (int)I("id",0);
		$data = array();
		$data["catId"] = (int)I("catId");
		$data["offlineTitle"] = I("offlineTitle");
		$data["offStartDate"] = I("offStartDate");
		$data["offEndDate"] = I("offEndDate");
		$data["offFile"] = I("offFile");
		$data["offlinePrice"] = I("offlinePrice");
		$data["offlineTeacher"] = I("offlineTeacher");
		$data["offlineFor"] = I("offlineFor");
		$data["offlineTeacherIntro"] = I("offlineTeacherIntro");
		$data["offlineVipPrice"] = I("offlineVipPrice");
		$data["isShow"] = (int)I("isShow",0);
		$data["offlineContent"] = I("offlineContent");
		dump($data["offlineContent"]);die;
		$data["offlineKey"] = I("offlineKey");
		$data["areaId1"] = (int)I("areaId1");
		$data["areaId2"] = (int)I("areaId2");
		//$data["areaId3"] = (int)I("areaId3");
		$data["staffId"] = (int)session('WST_STAFF.staffId');
	    if($this->checkEmpty($data,true)){	
		    $rs = $this->where("offlineId=".(int)I('id',0))->save($data);
			if(false !== $rs){
				$rd['status']= 1;
				
			}
		}
		return $rd;
	 } 
	 /**
	  * 获取指定对象
	  */
     public function get(){
		return $this->where("offlineId=".(int)I('id'))->find();
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage(){
	 	$sql = "select a.offlineTitle,a.offlineId,a.isShow,a.createTime,a.offStartDate,a.offEndDate,c.catName,s.staffName
	 	    from __PREFIX__offline a,__PREFIX__offline_cats c,__PREFIX__staffs s 
	 	    where a.catId=c.catId and a.staffId = s.staffId ";
	 	if(I('offlineTitle')!='')$sql.=" and offlineTitle like '%".WSTAddslashes(I('offlineTitle'))."%'";
	 	$sql.=' order by offlineId desc';
		return $this->pageQuery($sql);
	 }
	 
	 
	public function  querysign() {
		
		$sql="select s.*,o.offlineTitle,o.offlinePrice, o.offlineVipPrice, o.offStartDate, o.offEndDate from  __PREFIX__offline_sign s left join __PREFIX__offline o ON s.offlineId=o.offlineId where o.offlineId>0  ";
		
		return $this->pageQuery($sql);
	}
	 
	public function delsign(){
		$rd = array('status'=>-1);
		$rs = D('offline_sign')->delete((int)I('sign_id'));
		if(false !== $rs){
			$rd['status']= 1;
		}
		return $rd;
	}
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
	     $sql = "select * from __PREFIX__offline where isShow =1 order by offlineId desc";
		 $rs = $this->query($sql);
		 return $rs;
	  }
	  
	  
	  
	 /**
	  * 删除
	  */
	  
	  
	 public function del(){
	 	$rd = array('status'=>-1);
	    $rs = $this->delete((int)I('id'));
		if(false !== $rs){
		   $rd['status']= 1;
		}
		return $rd;
	 }
	 /**
	  * 显示分类是否显示/隐藏
	  */
	 public function editiIsShow(){
	 	$rd = array('status'=>-1);
	 	if(I('id',0)==0)return $rd;
	 	$this->isShow = ((int)I('isShow')==1)?1:0;
	 	$rs = $this->where("offlineId=".(int)I('id',0))->save();
	    if(false !== $rs){
			$rd['status']= 1;
		}
	 	return $rd;
	 }
};
?>