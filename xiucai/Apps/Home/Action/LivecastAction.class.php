<?php
namespace Home\Action;
/**
 * ============================================================================
 * 直播控制器
 * 联系QQ:1149100178
 * ============================================================================
 * 会员控制器
 */
class LivecastAction extends BaseAction {
    /**
     * 跳去直播界面
     */
	 
	 public function index(){
         $page = D('Live')->getList();
         $pager = new \Think\Page($page['total'] , $page['pageSize'] , I());// 实例化分页类 传入总记录数和每页显示的记录数
         $page['pager'] = $pager->show();
         $this->assign('page' , $page);
//         dump($page);die;
		 $this->display('default/livecast_index');
		 
	 }

    /**
     * 直播课程详情
     */
    public function course() {
        if (I('id')){
            //课程详情
            $id = I('id');
            $course = D('course')->where("courseId = $id")->find();
            $catName = D('course_cats')->field('catName')->where("catId = {$course['courseCatId3']}")->find();
            $course['catName'] = $catName['catName'];
            $course['liveStartTime'] = strtotime($course['liveStartTime']);
            $course['liveEndTime'] = strtotime($course['liveEndTime']);
            $this->assign('course',$course);
            //导师详情
            $tutor = D('shops')->field('shopId,shopName,shopImg,shopDetails')->where("shopId = {$course['shopId']}")->find();
            $this->assign('tutor',$tutor);
//            dump($tutor);dump($course);die;
            //相关课程
            $cid = $course['courseCatId2'];
            $related = D('course')->where("courseCatId2 = $cid and courseCatId3 <> {$course['courseCatId3']}")->limit(4)->select();
            $this->assign('related',$related);
//            dump($related);die;
            //判断登录用户是否已经报名该课程
            $user = session('WST_USER');
            $re = D('course_record')->where("uid = {$user['userId']} and cid =".I('id'))->find();
            if ($re) {
                $this->assign('sign' , 2);
                $this->assign('sign_id' ,$res['id']);
            } else {
                $this->assign('sign' , 1);
            }
            $this->display('default/livecast_course');
        } else {
            redirect(U('Home/Livecast/index'));
        }

    }

    /**
     * 报名课程
     */
    public function sign() {
    	$this->isUserLogin();
        if ($_POST) {
            $_POST['ctime'] = time();
            $re = D('course_record')->add($_POST);
            if ($re) {
                D('course')->query("update __PREFIX__course set saleCount = saleCount + 1 where courseId = {$_POST['cid']}");
                echo 1;
            }
        }
    }
    
    /**
     * 直播页面
     */
    public function live() {
    /* 	$this->isUserLogin();
    	$user=session('WST_USER');
    	$cid=I('id');//报名id
    	$courseId=I('courseId');//课程id
    	$uid = $USER['userId'];
    	 $re = D('course_record')->where("uid = {$user['userId']} and cid =".I('id'))->find();
    	if(!$re){
    		
    		$this->error('没有报名该直播。。。');
    		
    	} 
    	
    	
    	$liveOne=D('course')->where(array('courseId'=>$courseId))->find();
    	if($liveOne){
    	$this->assign('lid',$liveOne['vid']);
    	} */
    
    	
    	$this->display('default/living');
       
    }
}