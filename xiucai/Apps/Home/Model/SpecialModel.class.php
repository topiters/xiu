<?php
namespace Home\Model;
/**
* 课程类 文件
* ==============================================
* 版权所有 2015-2016 http://www.chunni168.com
* ----------------------------------------------
* 这不是一个自由软件，未经授权不许任何使用和传播。
* ==============================================
* @date: 2016-11-15
* @author: top_iter、lnrp
* @email:2504585798@qq.com
* @version:1.0
*/
class SpecialModel extends BaseModel {
	
	
	/**
	 * 课程列表
	 */
	public function getList(){
		$c1Id = (int)I("c1Id");
		$c2Id = (int)I("c2Id");
        $sort = (int)I('sort');
		$sqla = "SELECT  s.shopId,s.shopName,s.shopImg,s.shopGoodat,i.name as shopIndustry,s.shopAnswers,s.shopScore ";
		//$sqlb = "SELECT max(shopPrice) maxShopPrice  ";
		$sqlc = " FROM __PREFIX__shops s join __PREFIX__shop_industry i on s.shopIndustry = i.id ";

		$sqld = "  ";
		//有分类查询增加where条件
		if ($c1Id>0 or $c2Id>0){
		    $where = " WHERE ";
        }

        //有一级分类
		if ($c1Id > 0){
			$where .= " goodsCatId1 = $c1Id ";
		}
		//有一级和二级分类
		if ($c2Id > 0 && $c1Id > 0){
			$where .= " AND goodsCatId2 = $c2Id";
		}
		//只有二级分类
		if ($c2Id > 0 && $c1Id = 0){
            $where .= " goodsCatId2 = $c2Id";
        }
        $orderBy = " order by shopId desc";
        //有排序
        if ($sort > 0 ){
            //按解答数排序
            if ($sort == 1){
                $orderBy = " order by shopAnswers desc";
            }
            //按满意度排序
            if ($sort == 0) {
                $orderBy = " order by shopScore desc";
            }
        }
        //sql语句
		$sqla = $sqla . $sqlc . $sqld . $where . $orderBy;
		$pages = $this->pageQuery($sqla, I('p'), 1);
		$rs["pages"] = $pages;
		$gcats["courseCatId1"] = $c1Id;
		$gcats["courseCatId2"] = $c2Id;
		//$rs["courseNav"] = self::getCourseNav($gcats);
		return $rs;
	}

    public function getNewsList($id) {
	    $num = D('questions')->where("shopId = $id and is_answered = 1")->count();
	    $limit = 1;
        $pager = new \Think\Page($num , $limit);
        $arr = D('questions')->where("shopId = $id and is_answered = 1")->limit($pager->firstRow,$limit)->select();
        $arr['page'] = $pager->show();
        foreach ($arr as $k => $v){
            //获取用户头像
            $userArr = D('Users')->get($v['userId']);
            $arr[$k]['userPhoto'] = $userArr['userPhoto'];
            //获取该问题的答案
            $answer = D('answers')->where("qid = {$v['id']} and shopId = {$id}")->find();
            $arr[$k]['answer'] = $answer['content'];
        }
        return $arr;
	}
}