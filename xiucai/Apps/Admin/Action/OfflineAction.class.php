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