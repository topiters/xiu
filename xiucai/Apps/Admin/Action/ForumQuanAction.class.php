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
class ForumQuanAction extends BaseAction {
    /**
     * 分页查询
     */
    public function quan() {
        $this->isLogin();
        $this->checkPrivelege('ltlb_00');
        $m = D('Admin/ForumQuan');
        $list = $m->getCatAndChild();
        $this->assign('List' , $list);
//        dump($list);die;
        $this->display("/forum/quan");
    }

    public function toEdit() {
        $this->isLogin();
        $m = D('Admin/ForumQuan');
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
        $m = D('Admin/ForumQuan');
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
        $m = D('Admin/ForumQuan');
        $rs = $m->del();
        $this->ajaxReturn($rs);
    }



    /**
     * 显示商品是否显示/隐藏
     */
    public function editiIsShow() {
        $this->isLogin();
        $this->checkPrivelege('ltlb_03');
        $m = D('Admin/ForumQuan');
        $rs = $m->editiIsShow();
        $this->ajaxReturn($rs);
    }

    /**
     * 修改名称
     */
    public function editName() {
        $this->isLogin();
        $m = D('Admin/ForumQuan');
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