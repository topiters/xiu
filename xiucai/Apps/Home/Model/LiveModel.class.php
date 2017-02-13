<?php
namespace Home\Model;
/**
 * ============================================================================
 * 联系QQ:707563272
 * ============================================================================
 */
class LiveModel extends BaseModel {
	/**
	 * 获取分页
	 */
	public function getList(){
        $sql = "select c.courseId,c.courseName,c.courseThums,c.saleCount,c.courseIntro,cc.catName,c.liveStartTime,c.liveEndTime,s.shopId,s.shopImg,s.shopName
	 	    from __PREFIX__course c,__PREFIX__course_cats cc,__PREFIX__shops s 
	 	    where c.courseCatId3=cc.catId and c.shopId = s.shopId and is_live = 2";
        $sql .= ' order by liveStartTime desc';
        $result= $this->pageQuery($sql);
        if($result['root']){
            foreach ($result['root'] as $k => $v) {
                $result['root'][$k]['liveStartTime'] = strtotime($v['liveStartTime']);
                $result['root'][$k]['liveEndTime'] = strtotime($v['liveEndTime']);
                if ($result['root'][$k]['liveEndTime'] < time()) {
                    $result['root'][$k]['invalid'] = 1;
                }
            }
        }
        return  $result;
	}
	
	/**
     * 近期直播数据
     */
    public function related() {
        $sql = "select c.courseId,c.courseName,c.courseThums,c.saleCount,c.courseIntro,cc.catName,c.liveStartTime,c.liveEndTime,s.shopId,s.shopImg,s.shopName
	 	    from __PREFIX__course c,__PREFIX__course_cats cc,__PREFIX__shops s 
	 	    where c.courseCatId3=cc.catId and c.shopId = s.shopId and is_live = 2";
        $sql .= ' order by liveStartTime desc';
        $result = $this->pageQuery($sql,0,3);
        if ($result['root']) {
            foreach ($result['root'] as $k => $v) {
                $result['root'][$k]['liveStartTime'] = strtotime($v['liveStartTime']);
                $result['root'][$k]['liveEndTime'] = strtotime($v['liveEndTime']);
            }
        }
        return $result;
	}

    /**
     * 乐尚说
     */
    public function live($id) {
        $sql = "select c.courseId,c.courseName,c.courseThums,c.saleCount,c.courseIntro,cc.catName,c.liveStartTime,c.liveEndTime,s.shopId,s.shopImg,s.shopName
	 	    from __PREFIX__course c,__PREFIX__course_cats cc,__PREFIX__shops s 
	 	    where c.courseCatId3=cc.catId and c.shopId = s.shopId and is_live = 2 and c.shopId = $id";
        $sql .= ' order by liveStartTime desc';
        $result = $this->pageQuery($sql , 0 , 6);
        if ($result['root']) {
            foreach ($result['root'] as $k => $v) {
                $result['root'][$k]['liveStartTime'] = strtotime($v['liveStartTime']);
                $result['root'][$k]['liveEndTime'] = strtotime($v['liveEndTime']);
                if ($result['root'][$k]['liveEndTime'] < time()) {
                    $result['root'][$k]['invalid'] = 1;
                }
            }
        }
        return $result;
    }
}