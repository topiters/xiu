<?php
 namespace Admin\Action;;
/**
*  权限控制文件
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
class RolesAction extends BaseAction{
	/**
	 * 跳到新增/编辑页面
	 */
	public function toEdit(){
		$this->isLogin();
	    $m = D('Admin/Roles');
    	$object = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('jsgl_02');
    		$object = $m->get();
    	}else{
    		$this->checkPrivelege('jsgl_01');
    		$object = $m->getModel();
    	}
    	$this->assign('object',$object);
		$this->view->display('/roles/edit');
	}
	/**
	 * 新增/修改操作
	 */
	public function edit(){
		$this->isLogin();
		$m = D('Admin/Roles');
    	$rs = array();
    	if(I('id',0)>0){
    		$this->checkPrivelege('jsgl_02');
    		$rs = $m->edit();
    	}else{
    		$this->checkPrivelege('jsgl_01');
    		$rs = $m->insert();
    	}
    	$this->ajaxReturn($rs);
	}
	/**
	 * 删除操作
	 */
	public function del(){
		$this->isLogin();
		$this->checkPrivelege('jsgl_03');
		$m = D('Admin/Roles');
    	$rs = $m->del();
    	$this->ajaxReturn($rs);
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('jsgl_00');
		$m = D('Admin/Roles');
    	$page = $m->queryByPage();
    	$pager = new \Think\Page($page['total'],$page['pageSize']);// 实例化分页类 传入总记录数和每页显示的记录数
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
        $this->display("/roles/list");
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Admin/Roles');
		$list = $m->queryList();
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
};
?>