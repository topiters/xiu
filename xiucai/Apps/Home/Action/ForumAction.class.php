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
        $this->isLogin();
        $user = session('WST_USER');
        //用户签到数据
        $sign = D('sign')->where("userId = {$user['userId']}")->find();
        $this->assign('sign' , $sign);
        //判断今天是否签过到 最后一次签到时间大于今天0点则表示签过到
        if ($sign['ctime'] < strtotime(date('Y-m-d'))){
            $this->assign('can',1);
        }
        //当日签到人数
        $today = strtotime(date('Y-m-d'));
        $tomorrow = strtotime(date('Y-m-d' , strtotime('+1 day')));
        $result = D('sign')->where("ctime > $today and ctime < $tomorrow")->count();
        $todaySign = $result;
        $this->assign('todaySign',$todaySign);

        $this->display('default/forum_index');
    }

    /**
     * 发布新帖
     */
    public function add() {
        $this->display('default/forum_add');
    }

    /**
     *
     */
    public function doAdd() {
        dump($_POST);
    }
    /**
     * 处理签到
     */
    public function sign() {
        $this->isLogin();
        if ($_POST['userId']) {
            //分为两种情况 第一次签到或非第一次签到
            $uid = $_POST['userId'];
            //查询是否为第一次签到
            $re = D('sign')->where("userId = $uid")->find();
            if (!$re){
                //第一次签到直接增加数据
                $_POST['ctime'] = time();
                $_POST['days'] = 1;
                $_POST['rows'] = 1;
                $id = D('sign')->add($_POST);
                if ($id) {
                    echo 1;
                    exit;
                }
            } else {
                //非第一次签到 更新签到记录时间及天数
                $now = time();
                $last = $re['ctime'];
                if ($now - $last > (60*60*24)){ //两次签到时间大于一天则将连续签到更新为0
                    $result = D('sign')->execute("update wst_sign set lastTime = {$re['ctime']},ctime = {$now},days = days + 1,rows = 1 where userId = {$uid}");
                    if ($result){
                        echo 1;
                    }
                }else{ //两次签到时间小于一天则连续签到+1
                    $result = D('sign')->execute("update wst_sign set lastTime = {$re['ctime']} , ctime = {$now},days = days + 1,rows = rows + 1 where userId = {$uid}");
                    if ($result) {
                        echo 1;
                    }
                }
            }



        }
    }

    //我的圈子
    public function myindex() {

        $this->display('default/my_index');

    }

}