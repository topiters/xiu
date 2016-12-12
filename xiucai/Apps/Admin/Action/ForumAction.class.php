<?php
namespace Admin\Action;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 论坛控制器
 */
class ForumAction extends BaseAction {

    public function toEdit() {
        $this->isLogin();
        $m = D('Admin/Forum');
        $object = array();
        if (I('id' , 0) > 0) {
            $this->checkPrivelege('ltlb_03');
            $object = $m->get(I('id' , 0));
        } else {
            $this->checkPrivelege('ltlb_03');
            if (I('parentId' , 0) > 0) {
                $object = $m->get(I('parentId' , 0));
                $object['parentId'] = $object['catId'];
                $object['catName'] = '';
                $object['catSort'] = 0;
                $object['catId'] = 0;
            } else {
                $object = $m->getModel();
            }
        }
//        dump($object);die;
        $this->assign('object' , $object);
        $this->view->display('/forum/edit');
    }

    /**
     * 新增/修改操作
     */
    public function edit() {
        $this->isLogin();
        $m = D('Admin/Forum');
        $rs = array();
        if (I('id' , 0) > 0) {
            $this->checkPrivelege('ltlb_03');
            $rs = $m->edit();
        } else {
            $this->checkPrivelege('ltlb_03');
            $rs = $m->insert();
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del() {
        $this->isLogin();
        $this->checkPrivelege('ltlb_03');
        $m = D('Admin/Forum');
        $rs = $m->del();
        $this->ajaxReturn($rs);
    }

    /**
     * 查看
     */
    public function toView() {
        $this->isLogin();
        $this->checkPrivelege('wzlb_00');
        $m = D('Admin/Articles');
        if (I('id') > 0) {
            $object = $m->get();
            $this->assign('object' , $object);
        }
        $this->view->display('/articles/view');
    }

    /**
     * 分页查询
     */
    public function index() {
        $this->isLogin();
        $this->checkPrivelege('ltlb_00');
        $m = D('Admin/Forum');
        $page = $m->queryByPage();
        $pager = new \Think\Page($page['total'] , $page['pageSize'] , I());// 实例化分页类 传入总记录数和每页显示的记录数
        $page['pager'] = $pager->show();
        $this->assign('Page' , $page);
        $this->assign('keyword' , I('keyword'));
        $this->display("/forum/list");
    }

    /**
     * 分页查询
     */
    public function reply() {
        $this->isLogin();
        $this->checkPrivelege('ltlb_02');
        $m = D('Admin/Forum');
        $id = I('id');
        $page = $m->replyQueryByPage($id);
        $pager = new \Think\Page($page['total'] , $page['pageSize'] , I());// 实例化分页类 传入总记录数和每页显示的记录数
        $page['pager'] = $pager->show();
        $this->assign('Page' , $page);
        $this->assign('keyword' , I('keyword'));
        $this->display("/forum/reply");
    }
    /**
     * 列表查询
     */
    public function queryByList() {
        $this->isLogin();
        $m = D('Admin/Articles');
        $list = $m->queryByList();
        $rs = array();
        $rs['status'] = 1;
        $rs['list'] = $list;
        $this->ajaxReturn($rs);
    }

    /**
     * 显示商品是否显示/隐藏
     */
    public function editiIsShow() {
        $this->isLogin();
        $this->checkPrivelege('ltlb_03');
        $m = D('Admin/Forum');
        $rs = $m->editiIsShow();
        $this->ajaxReturn($rs);
    }

    /**
     * 修改名称
     */
    public function editName() {
        $this->isLogin();
        $m = D('Admin/Forum');
        $rs = array('status' => -1);
        if (I('id' , 0) > 0) {
            $this->checkPrivelege('ltlb_03');
            $rs = $m->editName();
        }
        $this->ajaxReturn($rs);
    }
}

;
?>