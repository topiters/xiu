<?php
 namespace Admin\Model;
/**
*  教师段文件
* ==============================================
* 版权所有 2015-2016 http://www.chunni168.com
* ----------------------------------------------
* 这不是一个自由软件，未经授权不许任何使用和传播。
* ==============================================
* @date: 2016-11-21
* @author: top_iter、lnrp
* @email:2504585798@qq.com
* @version:
**/
class ShopsCatsModel extends BaseModel {
	 /**
	  * 获取列表
	  */
	  public function queryByList($shopId,$parentId){
		 return $this->where('shopId='.(int)$shopId.' and catFlag=1 and parentId='.(int)$parentId)->select();
	  }
	 
};
?>