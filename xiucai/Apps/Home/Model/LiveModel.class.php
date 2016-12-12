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
        $sql = "select c.courseId,c.courseName,c.courseIntro,cc.catName,c.liveStartTime,c.liveEndTime,s.shopId,s.shopImg,s.shopName
	 	    from __PREFIX__course c,__PREFIX__course_cats cc,__PREFIX__shops s 
	 	    where c.courseCatId3=cc.catId and c.shopId = s.shopId and is_live = 2";
        $sql .= ' order by liveTime desc';
        return $this->pageQuery($sql);
	}

}