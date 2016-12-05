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
        //总帖量
        $articleNum = D('articles')->where('isShow = 1')->count();
        $this->assign('articleNum',$articleNum);

        //推荐圈子
        $cats = D('article_cats')->field('catId,catName,totalNum,key')->where('parentId = 0 and catFlag = 1')->select();
        $this->assign('cats',$cats);
        //置顶帖子
        $top = D('articles')->field('articleId,c.catId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("wst_article_cats c on wst_articles.catId = c.catId")->where('wst_articles.isShow = 1 and isTop = 1')->select();
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
        $where = 'wst_articles.isShow = 1';
        $order = '';
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
        $total = D('articles')->where('isShow = 1')->count();
        $page = new \Think\Page($total,$limit);
        $article = D('articles')->field('articleId,c.catId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("wst_article_cats c on wst_articles.catId = c.catId")->where($where)->order($order)->limit($page->firstRow,$limit)->select();
        $pages = $page->show();
        $this->assign('pages',$pages);
        $this->assign('article' , $article);

        //推荐阅读
        $tuijian = D('articles')->where('isShow = 1')->order('commentNum desc')->limit(0,5)->select();
        $this->assign('tuijian' , $tuijian);
//        dump($tuijian);die;
        $this->display('default/forum_index');
    }

    /**
     * 发布新帖
     */
    public function add() {
        $this->isLogin();
        $cateArr1 = D('article_cats')->field('catId,catName')->where('parentId = 0')->select();
        $this->assign('cateArr1',$cateArr1);
//        dump($cateArr1);die;
        $this->display('default/forum_add');
    }

    /**
     * 获取二级分类
     */
    public function cats() {
        $cat = D('Home/ArticleCats');
        $arr = $cat->queryByList($_POST['catId']);
        $html = '';
        foreach ($arr as $v) {
            $html .= "<li id=\"{$v['catId']}\">{$v['catName']}</li>";
        }
        echo $html;
    }
    
    /**
     *
     */
    public function doAdd() {
        $this->isLogin();
        $_POST['staffId'] = session('WST_USER')['userId'];
        $_POST['createTime'] = time();
        $re = D('articles')->add($_POST);
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
                    $result = D('sign')->execute("update wst_sign set lastTime = {$re['ctime']},ctime = {$now},days = days + 1,rows = 0 where userId = {$uid}");
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

        $this->display('default/quanzi');
    }


    /**
     * 文章详页
     */
    public function article() {

        $this->display('default/article');
    }


}