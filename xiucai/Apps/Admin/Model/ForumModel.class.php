<?php
namespace Admin\Model;
/**
 *  课程分类文件
 * ==============================================
 * 版权所有 2015-2016 http://www.chunni168.com
 * ----------------------------------------------
 * 这不是一个自由软件，未经授权不许任何使用和传播。
 * ==============================================
 * @date   : 2016-11-21
 * @author : top_iter、lnrp
 * @email  :2504585798@qq.com
 * @version:
 **/
class ForumModel extends BaseModel {
    /**
     * 分页列表 D('forum')
     */
    public function queryByPage() {
        $sql = "select a.articleTitle,a.articleId,a.createTime,a.isTop,c.catName,s.loginName
	 	    from __PREFIX__forum a,__PREFIX__forum_cats c,__PREFIX__users s 
	 	    where a.parentCatId=c.catId and a.staffId = s.userId ";
        if (I('keyword') != '') $sql .= " and articleTitle like '%" . WSTAddslashes(I('keyword')) . "%'";
        $sql .= ' order by articleId desc';
        return $this->pageQuery($sql);
    }

    /**
     * 分页列表 D('forum')
     */
    public function replyQueryByPage($id) {
        $sql = "select a.content,a.id,a.ctime,s.loginName
	 	    from __PREFIX__forum_comment a,__PREFIX__users s 
	 	    where a.uid = s.userId AND a.aid = $id";
        if (I('keyword') != '') $sql .= " and content like '%" . WSTAddslashes(I('keyword')) . "%'";
        $sql .= ' order by id desc';
        return $this->pageQuery($sql);
    }
    /**
     * 新增
     */
    public function insert() {
        $rd = array('status' => -1);
        $data = array();
        $data["catName"] = I("catName");
        if ($this->checkEmpty($data , true)) {
            $data["parentId"] = I("parentId" , 0);
            $data["isShow"] = (int)I("isShow" , 1);
            $data["catSort"] = (int)I("catSort" , 0);
            $data["catFlag"] = 1;
            $rs = D('forum_cats')->add($data);
            if ($rs) {
                $rd['status'] = 1;
            }
        }
        //发生数据修改,清空分类缓存
        S('ftree' , null);
        return $rd;
    }

    /**
     * 修改
     */
    public function edit() {
        $rd = array('status' => -1);
        $id = (int)I("id" , 0);
        $data = array();
        $data["catName"] = I("catName");
        $data["catInfo"] = I("catInfo");
        if ($this->checkEmpty($data)) {
            $data["isShow"] = (int)I("isShow" , 1);
            $data["catSort"] = (int)I("catSort" , 0);
            $rs = D('forum_cats')->where("catFlag=1 and catId=" . $id)->save($data);
            if (false !== $rs) {
                $rd['status'] = 1;
            }
        }
        //发生数据修改,清空分类缓存
        S('ftree' , null);
        return $rd;
    }

    /**
     * 修改名称
     */
    public function editName() {
        $rd = array('status' => -1);
        $id = (int)I("id" , 0);
        $data = array();
        $data["catName"] = I("catName");
        if ($this->checkEmpty($data)) {
            $rs = D('forum_cats')->where("catFlag=1 and catId=" . $id)->save($data);
            if (false !== $rs) {
                $rd['status'] = 1;
            }
        }
        //发生数据修改,清空分类缓存
        S('ftree' , null);
        return $rd;
    }

    /**
     * 获取指定对象
     */
    public function get($id) {
        return D('forum_cats')->where("catId=" . (int)$id)->find();
    }

    /**
     * 获取列表
     */
    public function queryByList($pid = 0) {
        $rs = $this->where('catFlag=1 and parentId=' . (int)$pid)->select();
        return $rs;
    }

    /**
     * 获取树形分类
     */
    public function getCatAndChild() {
        //判断是否存在缓存
        $tree = S('ftree');
        if ($tree) {
            return $tree;
        }
        $data = D('forum_cats')->select();
        $rs1 = $this->getTree($data);
        //存一份缓存
        S('ftree' , $rs1 , 3600 * 24 * 3);
        return $rs1;
    }

    /**
     *    获取树形分类
     */
    public function getTree($data , $parentId = 0) {
        $arr = array();
        foreach ($data as $k => $v) {
            if ($v['parentId'] == $parentId && $v['catFlag'] == 1) {
                //再查找该分类下是否还有子分类
                $v['child'] = $this->getTree($data , $v['catId']);
                //统计child
                $v['childNum'] = count($v['child']);
                //将找到的分类放回该数组中
                $arr[] = $v;
            }
        }
        return $arr;
    }


    /**
     * 迭代获取下级
     * 获取一个分类下的所有子级分类id
     */
    public function getChild($pid = 1) {
        $model = D('course_cats');
        $data = $model->select();
        //获取该分类id下的所有子级分类id
        $ids = $this->_getChild($data , $pid , true);//每次调用都清空一次数组
        //把自己也放进来
        array_unshift($ids , $pid);
        return $ids;
    }

    public function _getChild($data , $pid , $isClear = false) {
        static $ids = array();
        if ($isClear)//是否清空数组
        {
            $ids = array();
        }
        foreach ($data as $k => $v) {
            if ($v['parentId'] == $pid && $v['catFlag'] == 1) {
                $ids[] = $v['catId'];//将找到的下级分类id放入静态数组
                //再找下当前id是否还存在下级id
                $this->_getChild($data , $v['catId']);
            }
        }
        return $ids;
    }


    /**
     * 删除
     */
    public function del() {
        $rd = array('status' => -1);
        $rs = $this->delete((int)I('id'));
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }

    /**
     * 显示分类是否显示/隐藏
     */
    public function editiIsTop() {
        $rd = array('status' => -1);
        if (I('id' , 0) == 0) return $rd;
        $this->isTop = ((int)I('isTop') == 1) ? 1 : 0;
        $rs = $this->where("articleId=" . (int)I('id' , 0))->save();
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }
}

;
?>