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
        //获取用户头像
        foreach ($article as $k => $v) {
            $userArr = D('Users')->get($v['staffId']);
            $article[$k]['userPhoto'] = $userArr['userPhoto'];
            $userArr = D('Users')->get($v['lastId']);
            $article[$k]['lastName'] = $userArr['loginName'];
        }
        $pages = $page->show();
        $this->assign('pages',$pages);
//        dump($article);die;
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
            $today = strtotime(date('Y-m-d'));
            $tomorrow = strtotime(date('Y-m-d' , strtotime('+1 day')));
            $num = D('forum')->where("createTime between $today and $tomorrow and staffId = {$_POST['staffId']}")->count();
            if ($num < 10){
                D('forum_users')->query("update __PREFIX__users set userScore = userScore + 2 , userTotalScore = userTotalScore + 2 where userId = {$_POST['staffId']}");
            }
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
                if ($now - $last > (60*60*24*2)){ //两次签到时间大于一天则将连续签到更新为0
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
            $article = D('forum')->field('articleId,c.catId,parentCatId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("__FORUM_CATS__ c on __FORUM__.catId = c.catId")->where($where)->order($order)->limit($page->firstRow , $limit)->select();
            //获取用户头像
            foreach ($article as $k => $v) {
                $userArr = D('Users')->get($v['staffId']);
                $article[$k]['userPhoto'] = $userArr['userPhoto'];
                $userArr = D('Users')->get($v['lastId']);
                $article[$k]['lastName'] = $userArr['loginName'];
            }
            $pages = $page->show();
            $this->assign('pages' , $pages);
//            dump($article);die;
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
                D('forum_cats')->query("update __PREFIX__forum_cats set totalNum = totalNum + 1 where catId = {$_POST['cid']}");
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
                D('forum_cats')->query("update __PREFIX__forum_cats set totalNum = totalNum - 1 where catId = {$_POST['cid']}");
                echo 1;
            }
        }
    }

    /**
     * 文章详页
     */
    public function article() {
        if ($_GET['id'] && $_GET['pid']){
            $user = session('WST_USER');
//            dump($user);die;
            $this->assign('user' , $user);
            //文章详情
            D('forum')->query("update __PREFIX__forum set readNum = readNum +1 where articleId = {$_GET['id']}");
            $article = D('forum')->where("articleId = {$_GET['id']}")->find();
            $re = D('forum_cats')->field('catId,catName')->where("catId = {$article['catId']}")->find();
            $article['catName'] = $re['catName'];
            $re = D('forum_cats')->field('catId,catName')->where("catId = {$article['parentCatId']}")->find();
            $article['parentCatName'] = $re['catName'];
            $re = D('users')->field('userId,loginName,userPhoto')->where("userId = {$article['staffId']}")->find();
            $article['userName'] = $re['loginName'];
            $article['userPhoto'] = $re['userPhoto'];
            //等级
            $user = D('Users')->get($article['staffId']);
            $rank = rankUser($user['userTotalScore']);
            $article['userRankId'] = $rank['rankId'];
            $article['userRankName'] = $rank['rankName'];
            $article['userScore'] = $user['userTotalScore'];
//            dump($article);die;
            $this->assign('article',$article);

            //最赞评论
            $laudest = D('forum_comment')->field('id,uid,cuid,loginName,userPhoto,userTotalScore,aid,parentId,content,laud,ctime')->join("__USERS__ u on uid = u.userId")->where("aid = {$_GET['id']} and parentId = 0 and laud <> 0")->order('laud desc')->limit(1)->select();
            if ($laudest == null){
                $this->assign('laudest' , $laudest);
            }else{
                $laudest[0]['islaud'] = D('forum_comment_laud')->where("uid = {$user['userId']} and cid = {$laudest[0]['id']}")->select();
                $laudest[0]['rank'] = rankUser($laudest[0]['userTotalScore']);
                $laudest[0]['children'] = D('forum_comment')->field('id,uid,cuid,type,loginName,userPhoto,userTotalScore,aid,parentId,content,laud,ctime')->join("__USERS__ u on uid = u.userId")->where("aid = {$_GET['id']} and parentId = {$laudest[0]['id']}")->order('ctime asc')->select();
                echo D('forum_comment')->getLastSql();
                foreach ($laudest[0]['children'] as $k => $v) {
                    $laudest[0]['children'][$k]['rank'] = rankUser($v['userTotalScore']);
                    if ($laudest[0]['children'][$k]['type'] == 1) {
                        $userArr = D('users')->field('userId,loginName')->where("userId = {$laudest[0]['children'][$k]['cuid']}")->find();
                        $laudest[0]['children'][$k]['cuName'] = $userArr['loginName'];
                        $laudest[0]['children'][$k]['content'] = '回复 <a href="' . U('Home/Users/Index' , array('id' => $laudest[0]['children'][$k]['cuid'])) . '">' . $laudest[0]['children'][$k]['cuName'] . '</a> ：' . $laudest[0]['children'][$k]['content'];
                    }
                }
//                dump($laudest);die;
                $this->assign('laudest' , $laudest);
            }

            //评论列表
            $totalComment = D('forum_comment')->where("aid = {$_GET['id']}")->count();
            $this->assign('totalComment' , $totalComment);//获取评论总数
            if ($totalComment == 0) {
                $this->assign('comment' , '');
            }else{
                $total = D('forum_comment')->where("aid = {$_GET['id']} and parentId = 0")->count();
                $limit = 10;
                $page = new \Think\Page($total , $limit);
                $start = $page->firstRow;
                $comment = D('forum_comment')->field('id,uid,cuid,loginName,userPhoto,userTotalScore,aid,parentId,content,laud,ctime')->join("__USERS__ u on uid = u.userId")->where("aid = {$_GET['id']} and parentId = 0")->order('ctime asc')->limit($start , $limit)->select();
//            echo D('forum_comment')->getLastSql();die;
                $pages = $page->show();
                $this->assign('pages' , $pages);
                foreach ($comment as $k => $v) {
                    $comment[$k]['islaud'] = D('forum_comment_laud')->where("uid = {$user['userId']} and cid = {$v['id']}")->select();
                    $comment[$k]['rank'] = rankUser($v['userTotalScore']);
                    $comment[$k]['children'] = D('forum_comment')->field('id,uid,cuid,type,loginName,userPhoto,userTotalScore,aid,parentId,content,laud,ctime')->join("__USERS__ u on uid = u.userId")->where("aid = {$_GET['id']} and parentId = {$v['id']}")->order('ctime asc')->select();
                    foreach ($comment[$k]['children'] as $kk => $vv) {
                        $comment[$k]['children'][$kk]['rank'] = rankUser($vv['userTotalScore']);
                        if ($comment[$k]['children'][$kk]['type'] == 1) {
                            $userArr = D('users')->field('userId,loginName')->where("userId = {$comment[$k]['children'][$kk]['cuid']}")->find();
                            $comment[$k]['children'][$kk]['cuName'] = $userArr['loginName'];
                            $comment[$k]['children'][$kk]['content'] = '回复 <a href="' . U('Home/Users/Index' , array('id' => $comment[$k]['children'][$kk]['cuid'])) . '">' . $comment[$k]['children'][$kk]['cuName'] . '</a> ：' . $comment[$k]['children'][$kk]['content'];
                        }
                    }
                }
//            dump($comment);die;
                $this->assign('comment' , $comment);
            }

            //圈子热门
            $hot = D('forum')->where("isShow = 1 and parentCatId = {$article['parentCatId']}")->order('commentNum desc')->limit(0 , 5)->select();
            $this->assign('hot' , $hot);
//            dump($hot);die;
            //当前登录用户是否存在该圈子
            $exist = D('forum_record')->where("cid = {$_GET['pid']} and uid = {$user['userId']}")->find();
            if ($exist){
                $exist = 1;
            }else{
                $exist = 0;
            }
            $this->assign('exist',$exist);

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
        $this->isLogin();
        if($_POST['aid'] && $_POST['uid']){
            $re = D('forum')->where("articleId = {$_POST['aid']} and staffId = {$_POST['uid']}")->delete();
            if ($re){
                echo 1;
            }
        }
    }

    /**
     * 处理点赞
     */
    public function addLaud() {
        $this->isLogin();
        if ($_POST['uid'] && $_POST['cid']){
            $re = D('forum_comment_laud')->add($_POST);
            if ($re) {
                D('forum_comment')->query("update __PREFIX__forum_comment set laud = laud + 1 where id = {$_POST['cid']}");
                $today = date("Y-m-d H:i:s" , strtotime(date('Y-m-d')));
                $tomorrow = date("Y-m-d H:i:s" , strtotime(date('Y-m-d' , strtotime('+1 day'))));
                $num = D('forum_comment_laud')->where("ctime between '$today' and '$tomorrow' and luid = {$_POST['luid']}")->count();
                if ($num < 10) {
                    D('forum_users')->query("update __PREFIX__users set userScore = userScore + 1 , userTotalScore = userTotalScore + 1 where userId = {$_POST['luid']}");
                }
                echo 1;
            }
        }
    }

    /**
     * 删除点赞
     */
    public function delLaud() {
        $this->isLogin();
        if ($_POST['uid'] && $_POST['cid']) {
            $re = D('forum_comment_laud')->where("uid = {$_POST['uid']} and cid = {$_POST['cid']}")->delete();
            if ($re) {
                D('forum_comment')->query("update __PREFIX__forum_comment set laud = laud - 1 where id = {$_POST['cid']}");
                D('forum_users')->query("update __PREFIX__users set userScore = userScore - 1 , userTotalScore = userTotalScore - 1 where userId = {$_POST['luid']}");
                echo 1;
            }
        }
    }

    /**
     * 用户发表评论
     */
    public function addReply() {
        $this->isLogin();
        $_POST['ctime'] = time();
//        dump($_POST);die;
        $re = D('forum_comment')->add($_POST);
        if ($re) {
            D('forum')->query("update __PREFIX__forum set commentNum = commentNum +1 where articleId = {$_POST['aid']}");
            D('forum')->query("update __PREFIX__forum set lastId = {$_POST['uid']} , lastTime = {$_POST['ctime']} where articleId = {$_POST['aid']}");
            $today = strtotime(date('Y-m-d'));
            $tomorrow = strtotime(date('Y-m-d' , strtotime('+1 day')));
            $num = D('forum_comment')->where("ctime between $today and $tomorrow and uid = {$_POST['uid']}")->count();
            if ($num < 10) {
                D('forum_users')->query("update __PREFIX__users set userScore = userScore + 1 , userTotalScore = userTotalScore + 1 where userId = {$_POST['uid']}");
            }
            echo 1;
        }else{
//            echo D('forum_comment')->getLastSql();
        }
    }
}