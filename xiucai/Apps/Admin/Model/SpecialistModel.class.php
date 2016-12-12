<?php
namespace Admin\Model;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 文章服务类
 */
class SpecialistModel extends BaseModel {

    /**
     * 修改
     */
    public function reply() {
        $rd = array('status' => -1);
        $id = (int)I("qId");
        $data = array();
        $arr["is_answered"] = 1;
        $data["content"] = I("content");
        $data["qId"] = $id;
        if ($this->checkEmpty($data , true)) {
            $rs = D('answers')->add($data);
            if (false !== $rs) {
                D('questions')->where("id=" . $id)->save($arr);
                $rd['status'] = 1;
            }
        }
        return $rd;
    }

    /**
     * 获取指定对象
     */
    public function get() {
        return D('questions')->where("id=" . (int)I('id'))->find();
    }

    /**
     * 分页列表
     */
    public function queryByPage() {
        $sql = "select a.id,a.title,a.ctime,a.is_answered,u.loginName
	 	    from __PREFIX__questions a,__PREFIX__users u 
	 	    where a.userId = u.userId ";
        if (I('keyword') != '') $sql .= " and title like '%" . WSTAddslashes(I('keyword')) . "%'";
        $sql .= ' order by ctime desc';
        return $this->pageQuery($sql);
    }

    /**
     * 分页列表
     */
    public function replyQueryByPage($qid) {
        $sql = "select a.id,a.content,a.ctime,s.shopName
	 	    from __PREFIX__answers a,__PREFIX__shops s 
	 	    where a.shopId = s.shopId AND qid = {$qid}";
        if (I('keyword') != '') $sql .= " and content like '%" . WSTAddslashes(I('keyword')) . "%'";
        $sql .= ' order by ctime desc';
        return $this->pageQuery($sql);
    }

    /**
     * 获取列表
     */
    public function queryByList() {
        $sql = "select * from __PREFIX__articles where isShow =1 order by articleId desc";
        $rs = $this->query($sql);
        return $rs;
    }

    /**
     * 删除
     */
    public function del() {
        $rd = array('status' => -1);
        $rs = D('questions')->delete((int)I('id'));
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }

    /**
     * 删除
     */
    public function delReply() {
        $rd = array('status' => -1);
        $rs = D('answers')->delete((int)I('id'));
        if (false !== $rs) {
            $rd['status'] = 1;
        }
        return $rd;
    }
};
?>