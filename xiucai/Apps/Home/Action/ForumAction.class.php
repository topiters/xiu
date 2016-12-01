<?php
namespace Home\Action;
/**
 * ============================================================================
 * 论坛控制器
 * 联系QQ:1149100178
 * ============================================================================
 * 会员控制器
 */
class ForumAction extends BaseAction {

    //圈子首页
    public function index() {

        //当日签到人数
        $today = strtotime(date('Y-m-d'));
        $tomorrow = strtotime(date('Y-m-d' , strtotime('+1 day')));
        $result = D('sign')->where("ctime > $today and ctime < $tomorrow")->count();
        $todaySign = $result;
        $this->assign('todaySign',$todaySign);


        $this->display('default/forum_index');
    }

    /**
     * 处理签到
     */
    public function sign() {
        if ($_POST['id']) {
            $_POST['ctime'] = time();
            $id = D('sign')->add($_POST);
            if ($id) {

            }
        }
    }

    //我的圈子
    public function myindex() {

        $this->display('default/my_index');

    }

}