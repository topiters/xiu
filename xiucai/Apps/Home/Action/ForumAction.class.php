<?php
namespace Home\Action;
use Think\Page;

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
        $user = session('WST_USER');
        //用户签到数据
        $sign = D('sign')->where("userId = {$user['userId']}")->find();
        $now = time();
        $last = $sign['ctime'];
        if ($now - $last > (60 * 60 * 24 * 2)) { //两次签到时间大于两天则将连续签到更新为0
            D('sign')->execute("update wst_sign set rows = 0 where userId = {$user['userId']}");
            $sign['rows'] = 0;
        }
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

        //总帖量
        $articleNum = D('forum')->where('isShow = 1')->count();
        $this->assign('articleNum',$articleNum);

        //推荐圈子
        $cats = D('forum_cats')->field('catId,catName,totalNum')->where('parentId = 0 and catFlag = 1')->select();
        $this->assign('cats',$cats);

        //置顶帖子
        $top = D('forum')->field('articleId,c.catId,parentCatId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("wst_forum_cats c on wst_forum.catId = c.catId")->where('wst_forum.isShow = 1 and isTop = 1')->select();
        //获取用户头像
        foreach ($top as $k=>$v){
            $userArr = D('Users')->get($v['staffId']);
            $top[$k]['userPhoto'] = $userArr['userPhoto'];
            $userArr = D('Users')->get($v['lastId']);
            $top[$k]['lastName'] = $userArr['loginName'];
        }
        $this->assign('top',$top);
        //        dump($top);die;
        //文章列表
        $where = 'wst_forum.isShow = 1';
        $order = 'articleId desc';
        if ($_GET['type'] == 'hot'){
            $order = 'readNum desc';
        }
        if ($_GET['type'] == 'new') {
            $order = 'createTime desc';
        }
        if ($_GET['type'] == 'reply') {
            $order = 'lastTime desc';
        }
        $limit = 20;
        $total = D('forum')->where($where)->count();
        $page = new \Think\Page($total,$limit);
        $article = D('forum')->field('articleId,c.catId,parentCatId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("wst_forum_cats c on wst_forum.catId = c.catId")->where($where)->order($order)->limit($page->firstRow,$limit)->select();
        $pages = $page->show();
        $this->assign('pages',$pages);
        $this->assign('article' , $article);

        //推荐阅读
        $tuijian = D('forum')->where('isShow = 1')->order('commentNum desc')->limit(0,5)->select();
        $this->assign('tuijian' , $tuijian);
//        dump($tuijian);die;
        $this->display('default/forum_index');
    }

    /**
     * 发布新帖
     */
    public function add() {
        $this->isLogin();
        $cateArr1 = D('forum_cats')->field('catId,catName')->where('parentId = 0')->select();
        $this->assign('cateArr1',$cateArr1);
//        dump($cateArr1);die;
        $this->display('default/forum_add');
    }

    /**
     * 获取二级分类
     */
    public function cats() {
        $cat = D('Home/ForumCats');
        $arr = $cat->queryByList($_POST['catId']);
        $html = '';
        foreach ($arr as $v) {
            $html .= "<li id=\"{$v['catId']}\">{$v['catName']}</li>";
        }
        echo $html;
    }
    
    /**
     * 处理发帖
     */
    public function doAdd() {
        $this->isLogin();
        $user = session('WST_USER');
        $_POST['staffId'] = $user['userId'];
        $_POST['createTime'] = time();
        $re = D('forum')->add($_POST);
        if ($re){
            echo 1;
        }
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

    /**
     * 圈子
     */
    public function quan() {
        if ($_GET['id']){
            //根据ID查询圈子详情
            $id = $_GET['id'];
            $cat = D('forum_cats')->where("catId = $id and catFlag = 1")->find();
            $this->assign('cat' , $cat);
//            dump($cat);die;
            $user = session('WST_USER');
            //判断当前登录用户与该圈子关系
            $quanUser = D('forum_record')->where("cid = $id and uid = {$user['userId']}")->find();
            $this->assign('quanUser', $quanUser);
//            dump($quanUser);die;
            //今日发帖
            $today = strtotime(date('Y-m-d'));
            $todayArticle = D('forum')->where("parentCatId = $id and createTime > {$today}")->count(articleId);
//            echo D('forum')->getLastSql();die;
            $this->assign('todayArticle',$todayArticle);
            //今日成员
            $todayMember = D('forum_record')->where("cid = $id and ctime > {$today}")->count(uid);
//            echo D('forum_record')->getLastSql();die;
            $this->assign('todayMember',$todayMember);
            //最近加入
            $latest = D('forum_record')->where("cid = $id")->order('ctime desc')->limit(10)->select();
            foreach ($latest as $k=>$v) {
                $userArr = D('Users')->get($v['uid']);
                $latest[$k]['userPhoto'] = $userArr['userPhoto'];
            }
//            dump($latest);die;
            $this->assign('latest',$latest);
            //查询子分类
            $catArr = D('forum_cats')->field('catId,catName')->where("parentId = $id and catFlag = 1")->select();
            $this->assign('catArr' , $catArr);
            //置顶帖子
            $top = D('forum')->field('articleId,c.catId,parentCatId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("wst_forum_cats c on wst_forum.catId = c.catId")->where("wst_forum.isShow = 1 and isTop = 1 and parentCatId = $id")->select();
            //获取用户头像
            foreach ($top as $k => $v) {
                $userArr = D('Users')->get($v['staffId']);
                $top[$k]['userPhoto'] = $userArr['userPhoto'];
                $userArr = D('Users')->get($v['lastId']);
                $top[$k]['lastName'] = $userArr['loginName'];
            }
            $this->assign('top' , $top);
            //文章列表
            $where = ' wst_forum.isShow = 1 ';
            if ($_GET['cat']) {
                $where .= " and wst_forum.catId = {$_GET['cat']}";
            }else{
                $where .= " and wst_forum.parentCatId = $id";
            }
            $order = 'articleId desc';
            if ($_GET['type'] == 'hot') {
                $order = ' readNum desc ';
            }
            if ($_GET['type'] == 'new') {
                $order = ' createTime desc ';
            }
            if ($_GET['type'] == 'reply') {
                $order = ' lastTime desc ';
            }
            $limit = 20;
            $total = D('forum')->where($where)->count();
            $this->assign('total',$total);//圈子总帖数
            $page = new \Think\Page($total , $limit);
            $article = D('forum')->field('articleId,c.catId,parentCatId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("wst_forum_cats c on wst_forum.catId = c.catId")->where($where)->order($order)->limit($page->firstRow , $limit)->select();
            $pages = $page->show();
            $this->assign('pages' , $pages);
            $this->assign('article' , $article);
            //更多圈子
            $cats = D('forum_cats')->field('catId,catName,totalNum')->where("parentId = 0 and catFlag = 1 and catId <> {$_GET['id']}")->select();
            $this->assign('cats' , $cats);
            $this->display('default/quanzi');
        }else{
            $this->redirect(U('Home/Forum/index'));
        }
    }

    /**
     * 加入圈子
     */
    public function addquan() {
        $this->isLogin();
        if ($_POST['uid'] && $_POST['cid']){
            $_POST['ctime'] = time();
            $re = D('forum_record')->add($_POST);
            if ($re){
                D('forum_cats')->query("update wst_forum_cats set totalNum = totalNum + 1 where catId = {$_POST['cid']}");
                echo 1;
            }
        }
    }

    /**
     * 退出圈子
     */
    public function delquan() {
        $this->isLogin();
        if ($_POST['uid'] && $_POST['cid']) {
            $re = D('forum_record')->where("uid = {$_POST['uid']} and cid = {$_POST['cid']}")->delete();
            if ($re) {
                D('forum_cats')->query("update wst_forum_cats set totalNum = totalNum - 1 where catId = {$_POST['cid']}");
                echo 1;
            }
        }
    }

    /**
     * 获取评论列表
     */
    protected function getComList($article , $parent_id = 0) {
        $arr = D('forum_comment')->where("aid = '".$article."'"." and parentId = '" . $parent_id . "'")->order("ctime desc")->select();
        if (empty($arr)) {
            return array();
        }
        foreach ($arr as $k => $v) {
            $userArr = D('Users')->get($v['uid']);
//            dump($userArr);
            $arr[$k]['userName'] = $userArr['loginName'];
            $arr[$k]['userPhoto'] = $userArr['userPhoto'];
            $arr[$k]["children"] = $this->getComList($article , $v["id"]);
        }
        return $arr;
    }

    /**
     * 文章详页
     */
    public function article() {
        if ($_GET['id']){
            D('forum')->query("update wst_forum set readNum = readNum +1 where articleId = {$_GET['id']}");
            $article = D('forum')->where("articleId = {$_GET['id']}")->find();
            $re = D('forum_cats')->field('catId,catName')->where("catId = {$article['catId']}")->find();
            $article['catName'] = $re['catName'];
            $re = D('forum_cats')->field('catId,catName')->where("catId = {$article['parentCatId']}")->find();
            $article['parentCatName'] = $re['catName'];
            $re = D('users')->field('userId,loginName,userPhoto')->where("userId = {$article['staffId']}")->find();
            $article['userName'] = $re['loginName'];
            $article['userPhoto'] = $re['userPhoto'];
//            dump($article);die;
            $this->assign('article',$article);
            //评论列表
            $totalComment = D('forum_comment')->where("aid = {$_GET['id']}")->count(); //获取评论总数
            $this->assign('totalComment' , $totalComment);
            $comment = $this->getComList($_GET['id']);
//            dump($comment);die;
            $this->assign('comment',$comment);
            //圈子热门
            $hot = D('forum')->where("isShow = 1 and parentCatId = {$article['parentCatId']}")->order('commentNum desc')->limit(0 , 5)->select();
            $this->assign('hot' , $hot);
//            dump($hot);die;

            $this->display('default/article');
        }else{
            $this->redirect(U('Home/Forum/index'));
        }
    }

    /**
     * 文章修改
     */
    public function mod() {
        $this->isLogin();
        $cateArr1 = D('forum_cats')->field('catId,catName')->where('parentId = 0')->select();
        $this->assign('cateArr1' , $cateArr1);
//        dump($cateArr1);die;
        $article = D('forum')->where("articleId = {$_GET['id']}")->find();
        $this->assign('article' , $article);
//        dump($article);die;
        $this->display('default/forum_mod');
    }
    
    /**
     * 处理修改
     */
    public function doUpdate() {
        $this->isLogin();
        $id = $_POST['articleId'];
        $re = D('forum')->where("articleId = $id")->save($_POST);
        if ($re) {
            echo 1;
        }
    }

    /**
     * 处理删除
     */
    public function doDel() {
        echo 1;
    }
}