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
        } else {
            redirect(U('Home/Livecast/index'));
        }
        $this->display('default/livecast_course');
	 }
    
}