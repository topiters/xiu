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
    /**
     * 专家问答界面
     */
	 
	 public function  index(){
//	     dump(session('WST_USER'));die;
         $this->assign('user',session('WST_USER'));
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


//         $brands = $rslist["brands"];
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
		 $this->display('default/specialist_index');
	 }

    public function ask() {
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

    public function tutor() {
        if ($_GET[id]){
            $this->display('default/specialist_tutor_info');
        }else{
            redirect(U('Home/specialist/index'));
        }
	 }
}