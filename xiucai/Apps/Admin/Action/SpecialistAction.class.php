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
class SpecialistAction extends BaseAction {
    /**
     * 跳到回复页面
     */
    public function reply() {
        $this->isLogin();
        $m = D('Admin/Specialist');
        $object = array();
        if (I('id') > 0) {
            $this->checkPrivelege('wdlb_02');
            $object = $m->get();
        }
        $this->assign('object' , $object);
//        dump($object);die;
        $this->view->display('/specialist/reply');
    }

    /**
     * 新增/修改操作
     */
    public function doReply() {
        $this->isLogin();
        $m = D('Admin/Specialist');
        $rs = array();
        if (I('id') > 0) {
            $this->checkPrivelege('wdlb_02');
            $rs = $m->reply();
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除操作
     */
    public function del() {
        $this->isLogin();
        $this->checkPrivelege('wdlb_03');
        $m = D('Admin/Specialist');
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
        $this->checkPrivelege('wdlb_00');
        $m = D('Admin/Specialist');
        $page = $m->queryByPage();
        $pager = new \Think\Page($page['total'] , $page['pageSize'] , I());// 实例化分页类 传入总记录数和每页显示的记录数
        $page['pager'] = $pager->show();
        $this->assign('Page' , $page);
        $this->assign('keyword' , I('keyword'));
        $this->display("/specialist/list");
    }

    /**
     * 分页查询
     */
    public function replyList() {
        $this->isLogin();
        $this->checkPrivelege('wdlb_02');
        $m = D('Admin/Specialist');
        $page = $m->replyQueryByPage(I('id'));
        $pager = new \Think\Page($page['total'] , $page['pageSize'] , I());// 实例化分页类 传入总记录数和每页显示的记录数
        $page['pager'] = $pager->show();
        $this->assign('Page' , $page);
        $this->assign('keyword' , I('keyword'));
        $this->display("/specialist/replyList");
    }

    /**
     * 删除操作
     */
    public function delReply() {
        $this->isLogin();
        $this->checkPrivelege('wdlb_03');
        $m = D('Admin/Specialist');
        $rs = $m->delReply();
        $this->ajaxReturn($rs);
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

}
?>