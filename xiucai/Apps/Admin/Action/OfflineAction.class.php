<?php
 namespace Admin\Action;;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 文章控制器
 */
class OfflineAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	// public function toEdit(){
		// $this->isLogin();
	    // $m = D('Admin/Offline');
    	// $object = array();
    	// if(I('id',0)>0){
    		// $this->checkPrivelege('xxhd_02');
    		// $object = $m->get();
    	// }else{
    		// $this->checkPrivelege('xxhd_01');
    		// $object = $m->getModel();
    	// }
    	// $m = D('Admin/OfflineCats');
    	// $this->assign('catList',$m->getCatLists());
    	// $this->assign('object',$object);
    	// $a = D('Admin/Areas');
    	// $area2=$a->where(array('areaId'=>$object['areaId2']))->find();
    	// if($area2){
    		// $this->assign('area2',$area2);
    	// }
    	
    	// $this->assign('areaList',$v=$a->queryShowByList(0));
    	// var_dump($v);
		// $this->view->display('/offline/edit');
	// }
	public function toEdit(){
		$this->isLogin();
	    $m = D('Admin/Offline');
    	$object = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('xxhd_02');
    		$object = $m->get();
    	}else{
    		$this->checkPrivelege('xxhd_01');
    		$object = $m->getModel();
    	}
    	$m = D('Admin/OfflineCats');
    	$this->assign('catList',$m->getCatLists());
    	$this->assign('object',$object);
//    	$a = D('Admin/Areas');
//    	$area2=$a->where(array('areaId'=>$object['areaId2']))->find();
//    	if($area2){
//    		$this->assign('area2',$area2);
//    	}
        $arr0['areaId'] = 1;
        $arr0['areaName'] = '北京市';
        $arr1['areaId'] = 2;
        $arr1['areaName'] = '上海市';
        $arr2['areaId'] = 3;
        $arr2['areaName'] = '广州市';
        $arr3['areaId'] = 4;
        $arr3['areaName'] = '深圳市';
        $arr4['areaId'] = 5;
        $arr4['areaName'] = '郑州市';
    	$arr = array();
    	$arr[] = $arr0;
        $arr[] = $arr1;
        $arr[] = $arr2;
        $arr[] = $arr3;
        $arr[] = $arr4;
    	$this->assign('areaList',$v=$arr);
//    	dump($v);die;
		$this->view->display('/offline/edit');
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Admin/Offline');
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('xxhd_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('xxhd_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('xxhd_03');
		$m = D('Admin/Offline');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	
	
	
	public function delsign(){
		$this->isLogin();
		//$this->checkPrivelege('xxhd_03');
		$m = D('Admin/Offline');
		$rs = $m->delsign();
		$this->ajaxReturn($rs);
	}
	
	
   /**
	 * 查看
	 */
	public function toView(){
		$this->isLogin();
		$this->checkPrivelege('xxhd_00');
		$m = D('Admin/Offline');
		if(I('id')>0){
			$object = $m->get();
			$this->assign('object',$object);
		}
		$this->view->display('/offline/view');
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('xxhd_00');
		$m = D('Admin/Offline');
    	$page = $m->queryByPage();
    	$pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign('offlineTitle',I('offlineTitle'));
        $this->display("/offline/list");
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Admin/Offline');
		$list = $m->queryByList();
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
	
	
	public function book(){
		$this->isLogin();
		$m = D('Admin/Offline');
		$page = $m->querysign();
		
		$pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
    	$page['pager'] = $pager->show();
    	
    	
    	$this->assign('Page',$page);
		
		
		
		$this->display("/offline/book");
	}
    /**
	 * 显示商品是否显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isLogin();
	 	$this->checkPrivelege('xxhd_02');
	 	$m = D('Admin/Offline');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }
};
?>