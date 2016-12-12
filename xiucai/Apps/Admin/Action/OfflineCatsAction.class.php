<?php
 namespace Admin\Action;;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 文章分类控制器
 */
class OfflineCatsAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function toEdit(){
		$this->isLogin();
	    $m = D('Admin/OfflineCats');
    	$object = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('xxfl_02');
    		$object = $m->get();
    	}else{
    		$this->checkPrivelege('xxfl_01');
    		$object = $m->getModel();
    		$object['parentId'] = I('parentId',0);
    	}
    	$this->assign('object',$object);
		$this->view->display('/offlinecats/edit');
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Admin/OfflineCats');
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('xxfl_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('xxfl_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 修改名称
	 */
	public function editName(){
		$this->isLogin();
		$m = D('Admin/OfflineCats');
    	$rs = array('status'=>-1);
    	if(I('id',0)>0){
    		$this->checkPrivelege('xxfl_02');
    		$rs = $m->editName();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('xxfl_03');
		$m = D('Admin/OfflineCats');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('xxfl_00');
		$m = D('Admin/OfflineCats');
    	$list = $m->queryByList(I('parentId',0));
    	$this->assign('List',$list);
        $this->display("/offlinecats/list");
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Admin/OfflineCats');
		$list = $m->queryByList(I('id',0));
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
    /**
	 * 显示分类是否显示/隐藏
	 */
	 public function editiIsShow(){
	 	$this->isLogin();
	 	$this->checkPrivelege('xxfl_02');
	 	$m = D('Admin/OfflineCats');
		$rs = $m->editiIsShow();
		$this->ajaxReturn($rs);
	 }

};
?>