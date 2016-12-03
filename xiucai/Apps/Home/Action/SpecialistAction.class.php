<?php
namespace Home\Action;
/**
 * ============================================================================
 * 
 * 联系QQ:1149100178
 * ============================================================================
 * 会员控制器
 */
class SpecialistAction extends BaseAction {

    public function _initialize() {
        $this->assign('user' , session('WST_USER'));
//        dump(session('WST_USER'));die;
    }
    /**
     * 专家问答界面
     */
	 
	 public function  index(){
         $mcourses = D('Home/Special');
         $mcoursesCat = D('Home/CourseCats');
         $c1Id = (int)I("c1Id");//如果没有分类默认热门课程分类
         $c2 = (int)I("c2Id");
         if ($c2) {
             $this->assign('c2Id' , $c2);
         }
         if ($c1Id) {
             $this->assign('c1Id' , $c1Id);
//              var_dump($c1Id);
         }
         $rslist = $mcourses->getList();
         $pages = $rslist['pages'];
         foreach ($pages['root'] as $k=>$v) {
             $pages['root'][$k]['shopGoodat'] = explode(',' , $v['shopGoodat']);
             $sid = $v['shopId'];
             $qArr = D('questions')->field('id,title')->where("shopId = {$sid} and is_answered = 1")->order('id desc')->limit(2)->select();
             $pages['root'][$k]['shopQuestions'] = $qArr;
         }
//         dump($pages);
//         die;
         $this->assign('pages' , $pages);
         $c1 = $c1Id ? $c1Id : 11;
         if ($c1) {
             $c2cat = $mcoursesCat->queryByList($c1);
             $this->assign("c2cat" , $c2cat);
         }
         //提问题者总数
         $people = D('questions')->group('userId')->count('DISTINCT userId');
         $this->assign('people',$people);
         //问题总数
         $questions = D('questions')->where("is_answered = 1")->count();
         $this->assign('questions' , $questions);
		 $this->display('default/specialist_index');
	 }

	 /*
	  * 处理提问
	  */
    public function ask() {
        $this->isLogin();
        if ($_GET){
            $_GET['ctime'] = time();
            $re = D('questions')->add($_GET);
            if ($re){
                echo 1;
            }else{
                echo 0;
            }
        }
	 }

    /**
     * 导师详页
     */
    public function tutor() {
        if ($_GET[id]){
            //获取店铺信息
            $sid = $_GET['id'];
            $arr = D('shops')->where("shopId = $sid")->find();
            $arr['shopGoodat'] = explode(',' , $arr['shopGoodat']);
            $arr['shopIndustry'] = D('shop_industry')->field('name')->where("id = {$arr['shopIndustry']}")->find();
            $arr['shopIndustry'] = $arr['shopIndustry']['name'];
            $courseNum = D('Course')->getShopsCourse($sid);
            $arr['courseNum'] = $courseNum['total'];
            //判断当前登录用户是否关注该导师
            if ($_SESSION['WSTMALL']['WST_USER']['userId']){
                $result = D('follow')->field('id')->where("userId = {$_SESSION['WSTMALL']['WST_USER']['userId']} and shopId = {$sid}")->find();
                if ($result){
                    $this->assign('following',$result);
                }
            }
            //粉丝列表
            $idArr = D('follow')->field('userId')->where("shopId = $sid")->select();
            foreach ($idArr as $k => $v) {
                $userArr = D('Users')->get($v['userId']);
                $idArr[$k]['userPhoto'] = $userArr['userPhoto'];
                $idArr[$k]['loginName'] = $userArr['loginName'];
            }
//            dump($idArr);die;
            $arr['shopFollowers'] = $idArr;
//            dump($arr);exit;
            $this->assign('sArr',$arr);
            //热播课程
            $course = D('Course')->getHotCourse($arr['shopId']);
            $this->assign('course',$course);
            $this->display('default/specialist_tutor_info');
        }else{
            redirect(U('Home/specialist/index'));
        }
    }

    /**
     * 导师问题列表页
     */
    public function questionList() {
        $this->isLogin();
        if ($_GET[id]) {
            //获取店铺信息
            $sid = $_GET['id'];
            $arr = D('shops')->where("shopId = $sid")->find();
            $arr['shopGoodat'] = explode(',' , $arr['shopGoodat']);
            $arr['shopIndustry'] = D('shop_industry')->field('name')->where("id = {$arr['shopIndustry']}")->find();
            $arr['shopIndustry'] = $arr['shopIndustry']['name'];
            $courseNum = D('Course')->getShopsCourse($sid);
            $arr['courseNum'] = $courseNum['total'];
            //判断当前登录用户是否关注该导师
            if ($_SESSION['WSTMALL']['WST_USER']['userId']) {
                $result = D('follow')->field('id')->where("userId = {$_SESSION['WSTMALL']['WST_USER']['userId']} and shopId = {$sid}")->find();
                if ($result) {
                    $this->assign('following' , $result);
                }
            }
            //粉丝列表
            $idArr = D('follow')->field('userId')->where("shopId = $sid")->select();
            foreach ($idArr as $k => $v) {
                $userArr = D('Users')->get($v['userId']);
                $idArr[$k]['userPhoto'] = $userArr['userPhoto'];
                $idArr[$k]['loginName'] = $userArr['loginName'];
            }
//            dump($idArr);die;
            $arr['shopFollowers'] = $idArr;
//            dump($arr);exit;
            $this->assign('sArr' , $arr);
            //问题列表
            $qArr = D('Special')->getNewsList($sid);

            $this->assign('page' , $qArr['page']);
            unset($qArr['page']);
//            dump($qArr);die;
            $this->assign('qArr' , $qArr);
            $this->display('default/specialist_tutor_question');
        } else {
            redirect(U('Home/specialist/index'));
        }
    }
    
    /**
     * 处理关注
     */
    public function add() {
        $this->isLogin();
        if ($_POST['userId'] && $_POST['shopId']){
            $uid = $_POST['userId'];
            $sid = $_POST['shopId'];
            //先看是否有关注关系
            $re = D('follow')->where("userId = $uid and shopId = $sid")->find();
            if ($re){
                echo 2;
                exit;
            }else{//没有则添加记录 并为该导师粉丝数加1
                $re = D('follow')->add($_POST);
                if ($re){
                    D('shops')->query("update wst_shops set shopFollower = shopFollower + 1 where shopId = $sid");
                    echo 1;
                    exit;
                }else{
                    exit;
                }
            }
        }
    }
    
    /**
     * 取消关注
     */
    public function del() {
        $this->isLogin();
        if ($_POST['userId'] && $_POST['shopId']) {
            $uid = $_POST['userId'];
            $sid = $_POST['shopId'];
            $re = D('follow')->where("userId = $uid and shopId = $sid")->delete();
            if ($re){
                D('shops')->query("update wst_shops set shopFollower = shopFollower - 1 where shopId = $sid");
                echo 1;
                exit;
            }else{
                echo 'error';
                exit;
            }
        }
    }
}