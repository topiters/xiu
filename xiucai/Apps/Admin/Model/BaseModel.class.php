<?php
 namespace Admin\Model;
/**
*  xx文件
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
use Think\Model;
class BaseModel extends Model {
    /**
     * 用来处理内容中为空的判断
     */
	public function checkEmpty($data,$isDie = false){
	    foreach ($data as $key=>$v){
			if(trim($v)==''){
				if($isDie)die("{'status':-1,'key':'$key'}");
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 输入sql调试信息
	 */
	public function logSql($m){
		echo $m->getLastSql();
	}
    /**
	 * 获取一行记录
	 */
	public function queryRow($sql){
		$plist = $this->query($sql);
		return empty($plist)?array():$plist[0];
	}
	

	/**
	 * 格式化查询语句中传入的in 参与，防止sql注入
	 * @param unknown $split
	 * @param unknown $str
	 */
	public function formatIn($split,$str){
		if(is_array($str)){
			$strdatas = $str;
		}else{
			$strdatas = explode($split,$str);
		}
		$data = array();
		for($i=0;$i<count($strdatas);$i++){
			$data[] = (int)$strdatas[$i];
		}
		$data = array_unique($data);
		return implode($split,$data);
	}
};
?>