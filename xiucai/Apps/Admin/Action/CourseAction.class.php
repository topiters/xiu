<?php
 namespace Admin\Action;;

class CourseAction extends BaseAction{

   /**
	 * 查看
     */
	public function toView(){
		$this->isLogin();
		$this->checkPrivelege('splb_00');
		$m = D('Admin/Course');
		if(I('id')>0){
			$object = $m->get();
			$this->assign('object',$object);
		}else{
			die("课程不存在!");
		}
		$this->view->display('/course/view');
	}
    /**
	 * 查看
	 */
	public function toPenddingView(){
		$this->isLogin();
		$this->checkPrivelege('spsh_00');
		$m = D('Admin/Course');
		if(I('id')>0){
			$object = $m->get();
			$this->assign('object',$object);
			//获取课程分类信息
			$m = D('Admin/CourseCats');
			$this->assign('courseCatsList',$m->queryByList());
			//获取商家课程分类
			$m = D('Admin/ShopsCats');
			$this->assign('shopCatsList',$m->queryByList($object['shopId'],0));
		}else{
			die("课程不存在!");
		}
		$this->view->display('/course/view_pendding');
	}
	/**
	 * 分页查询
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('splb_00');
		//获取地区信息
		$m = D('Admin/Areas');
		$this->assign('areaList',$m->queryShowByList(0));
		//获取课程分类信息
		$m = D('Admin/CourseCats');
		$this->assign('courseCatsList',$m->queryByList());
		$m = D('Admin/Course');
    	$page = $m->queryByPage();
    	$pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign('shopName',I('shopName'));
    	$this->assign('courseName',I('courseName'));
    	$this->assign('areaId1',I('areaId1',0));
    	$this->assign('areaId2',I('areaId2',0));
    	$this->assign('courseCatId1',I('courseCatId1',0));
    	$this->assign('courseCatId2',I('courseCatId2',0));
    	$this->assign('courseCatId3',I('courseCatId3',0));
    	$this->assign('isAdminBest',I('isAdminBest',-1));
    	$this->assign('isAdminRecom',I('isAdminRecom',-1));
        $this->display("/course/list");
	}
    /**
	 * 分页查询
	 */
	public function queryPenddingByPage(){
		$this->isLogin();
		$this->checkPrivelege('spsh_00');
		//获取地区信息
		$m = D('Admin/Areas');
		$this->assign('areaList',$m->queryShowByList(0));
		//获取课程分类信息
		$m = D('Admin/CourseCats');
		$this->assign('courseCatsList',$m->queryByList());
		$m = D('Admin/Course');
    	$page = $m->queryPenddingByPage();
    	$pager = new \Think\Page($page['total'],$page['pageSize'],I());// 实例化分页类 传入总记录数和每页显示的记录数
    	$pager->setConfig('header','个会员');
    	$page['pager'] = $pager->show();
    	$this->assign('Page',$page);
    	$this->assign('shopName',I('shopName'));
    	$this->assign('courseName',I('courseName'));
    	$this->assign('areaId1',I('areaId1',0));
    	$this->assign('areaId2',I('areaId2',0));
    	$this->assign('courseCatId1',I('courseCatId1',0));
    	$this->assign('courseCatId2',I('courseCatId2',0));
    	$this->assign('courseCatId3',I('courseCatId3',0));
        $this->display("/course/list_pendding");
	}
	/**
	 * 列表查询
	 */
    public function queryByList(){
    	$this->isLogin();
		$m = D('Admin/Course');
		$list = $m->queryByList();
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
    /**
	 * 列表查询[获取启用的区域信息]
	 */
    public function queryShowByList(){
    	$this->isLogin();
		$m = D('Admin/Course');
		$list = $m->queryShowByList();
		$rs = array();
		$rs['status'] = 1;
		$rs['list'] = $list;
		$this->ajaxReturn($rs);
	}
	/**
	 * 修改待审核课程状态
	 */
	public function changePenddingCourseStatus(){
		$this->isLogin();
		$this->checkPrivelege('spsh_04');
		$m = D('Admin/Course');
		$rs = $m->changeCourseStatus();
		$this->ajaxReturn($rs);
	}
    /**
	 * 修改课程状态
	 */
	public function changeCourseStatus(){
		$this->isLogin();
		$this->checkPrivelege('splb_04');
		$m = D('Admin/Course');
		$rs = $m->changeCourseStatus();
		$this->ajaxReturn($rs);
	}
	public function becheChangeCourseStatus(){
		$this->isLogin();
		$this->checkPrivelege('splb_04');
		$id = I('post.id');
	}

    /**
	 * 获取待审核的课程数量
	 */
	public function queryPenddingCourseNum(){
		$this->isLogin();
    	$m = D('Admin/Course');
    	$rs = $m->queryPenddingCourseNum();
    	$this->ajaxReturn($rs);
	}
    /**
	 * 批量设置精品
	 */
	public function changeBestStatus(){
		$this->isLogin();
		$this->checkPrivelege('splb_04');
		$m = D('Admin/Course');
		$rs = $m->changeBestStatus();
		$this->ajaxReturn($rs);
	}
    /**
	 * 批量设置推荐
	 */
	public function changeRecomStatus(){
		$this->isLogin();
		$this->checkPrivelege('splb_04');
		$m = D('Admin/Course');
		$rs = $m->changeRecomStatus();
		$this->ajaxReturn($rs);
	}
	
};
?>