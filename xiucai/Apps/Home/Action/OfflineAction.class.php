<?php
namespace Home\Action;
/**
 * ============================================================================
 * 论坛控制器
 * 联系QQ:1149100178
 * ============================================================================
 * 会员控制器
 */
class OfflineAction extends BaseAction {
	
	
	
	public function __construct(){
		parent::__construct();
	
	  $cate=D('offline_cats')->where(array('catFlag'=>1,'isShow'=>1))->select();
	  $this->assign('cate',$cate);
	}
    /**
     * 
     */
	  //首页
	 public function index(){
	 	
	 	$cat=I('cat');//子类
	 	$pcat=I('pcat');//父类
	 	$monthid=I("yearId");//按年检索
	 	$yearid=I("monthId");//按月检索
	 	$areaId=I("areaId");//按地区检索
	 	if($cat){
	 	$map['catId']=$cat;
	 	}
	 	if($pcat){
	 		$map['parentId']=$pcat;
	 	}
	 	$map['isShow']=1;
	 	if($yearid){
	 		
	 		$map['offStartDate']=array('like','%'.$yearid.'%');
	 		
	 	}
	 	if($monthid){
	 	
	 		$map['offStartDate']=array('like','%'.$monthid.'%');
	 	
	 	}
	 	
	 	$offlist=D('offline')->where($map)->limit(20)->select();
	 	$this->assign('offlist',$offlist);
	 	
		 
		 $this->display('default/offline_index');
		 
	 }
	 //详情
	 public function details(){
	 	  
	 	//$this->isLogin();
	 	$USER = session('WST_USER');
	 	if($USER){
	 	$this->assign("uid",$USER['userId']);	
	 		
	 		
	 	}
	 	$catId=I('catId');//子类
	 	$details=D('offline')->where(array('offlineId'=>$catId))->find();
	 	$this->assign('details',$details);
	 	//var_dump($details);
	 	if($details['areaId2']){
	 		$areaName=D('areas')->where(array('area'=>$details['areaId2']))->find();//举办地点
	 		$this->assign('areaName',$areaName);
	 		
	 		//exit;
	 		
	 	}
	 	//近期课程
	 	$jqcourse=D('course')->where(array('isSale'=>1))->limit(3)->order('courseId  desc')->select();
	 	$this->assign('jqcourse',$jqcourse);
	 	
	 	
	 	$this->display('default/offline_details');
	 		
	 }
	 
	 //报名
	public function  takeInac(){
		$rs=1;
		$uname=I('uname');
		$mobile=I("umobile");
		$uid=I('uid');
		$offlineid=I('offlineid');
		//$forme=I('typeId');//是否给自自己报名
		$data['userId']=$uid;
		$data['mobile']=$mobile;
		$data['offlineId']=$offlineid;
		$data['uname']=$uname;
		$data['type']=I('type') ;//1为自己报名
		if($data['type']){
		$repeatId=D('offline_sign')->where(array('userId'=>$uid,'offlineId'=>$offlineid,'type'=>$data['type']))->find();//判是否报名
		if($repeatId){
			
			$rs='-2';
			$this->ajaxReturn($rs);
			die();
			
		}
		}
		$result=D('offline_sign')->add($data);
		//var_dump(D('offline_sign')->getLastSql());
		if($result){
			
			$this->ajaxReturn($rs);
			
		}else{
			
			$rs='-1';
			$this->ajaxReturn($rs);
		}
		
		
	
		
	}
	 
	 
	 
	 
	/**
	 * 发送验证邮件
	 */
	public function getEmailVerify(){
		$rs = array('status'=>-1);
		$keyFactory = new \Think\Crypt();
		$key = $keyFactory->encrypt("0_".session('findPass.userId')."_".time(),C('SESSION_PREFIX'),30*60);
		$url = "http://".$_SERVER['HTTP_HOST'].U('Home/Users/toResetPass',array('key'=>$key));
		$html="您好，会员 ".session('findPass.loginName')."：<br>
		您在".date('Y-m-d H:i:s')."发出了重置密码的请求,请点击以下链接进行密码重置:<br>
		<a href='".$url."'>".$url."</a><br>
		<br>如果您的邮箱不支持链接点击，请将以上链接地址拷贝到你的浏览器地址栏中。<br>
		该验证邮件有效期为30分钟，超时请重新发送邮件。<br>
		<br><br>*此邮件为系统自动发出的，请勿直接回复。";
		$sendRs = WSTSendMail(session('findPass.userEmail'),'密码重置',$html);
		if($sendRs['status']==1){
			$rs['status'] = 1;
		}else{
			$rs['msg'] = $sendRs['msg'];
		}
		$this->ajaxReturn($rs);
	}
	
    /**
     * 跳到重置密码
     */
    public function toResetPass(){
    	$key = I('key');
	    $keyFactory = new \Think\Crypt();
		$key = $keyFactory->decrypt($key,C('SESSION_PREFIX'));
		$key = explode('_',$key);
		if(time()>floatval($key[2])+30*60)$this->error('连接已失效！');
		if(intval($key[1])==0)$this->error('无效的用户！');
		session('REST_userId',$key[1]);
		session('REST_Time',$key[2]);
		session('REST_success','1');
		$this->display('default/forget_pass3');
    }
    
    /**
     * 跳去用户登录的页面
     */
    public function toLoginBox(){
        if(isset($_COOKIE["loginName"])){
			$this->assign('loginName',$_COOKIE["loginName"]);
		}else{
			$this->assign('loginName','');
		}
		$this->assign('qqBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/qqlogin.php"));
		$this->assign('wxBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/wxlogin.php"));
    	$this->display('default/login_box');
    }
    
    /**
     * 查看积分记录
     */
    public function toScoreList(){
    	$this->isUserLogin();
    	$um = D('Home/Users');
    	$user = $um->getUserById(array("userId"=>session('WST_USER.userId')));
    	$this->assign("userScore",$user['userScore']);
    	$this->assign("umark","toScoreList");
    	$this->display("default/users/score_list");
    }
    
    /**
     * 查看积分记录
     */
    public function getScoreList(){
    	$this->isUserLogin();
    	$m = D('Home/UserScore');
    	$rs = $m->getScoreList();
    	$this->ajaxReturn($rs);
    }
    
    /**
     * QQ登录回调方法
     */
	public function qqLoginCallback(){
    	header ( "Content-type: text/html; charset=utf-8" );
    	vendor ( 'ThirdLogin.QqLogin' );

    	$appId = $GLOBALS['CONFIG']["qqAppId"];
    	$appKey = $GLOBALS['CONFIG']["qqAppKey"];
    	//回调接口，接受QQ服务器返回的信息的脚本
    	$callbackUrl = WSTDomain()."/Wstapi/thridLogin/qqlogin.php";
    	//实例化qq登陆类，传入上面三个参数
    	$qq = new \QqLogin($appId,$appKey,$callbackUrl);
    	//得到access_token验证值
    	$accessToken = $qq->getToken();
    	if(!$accessToken){
    		$this->redirect("Home/Users/login");
    	}
    	//得到用户的openid(登陆用户的识别码)和Client_id
    	$arr = $qq->getClientId($accessToken);
    	if(isset($arr['client_id'])){
    		$clientId = $arr['client_id'];
    		$openId = $arr['openid'];
    		$um = D('Home/Users');
    		//已注册，则直接登录
    		if($um->checkThirdIsReg(1,$openId)){
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 1;
    			$rd = $um->thirdLogin($obj);
    			if($rd["status"]==1){
    				$this->redirect("Home/Index/index");
    			}else{
    				$this->redirect("Home/Users/login");
    			}
    		}else{
    			//未注册，则先注册
    			$arr = $qq->getUserInfo($clientId,$openId,$accessToken);
    			$obj["userName"] = $arr["nickname"];
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 1;
    			$obj["userPhoto"] = $arr["figureurl_2"];
    			$um->thirdRegist($obj);
    			$this->redirect("Home/Index/index");
    		}
    	}else{
    		$this->redirect("Home/Users/login");
    	}
    }
    
    /**
     * 微信登录回调方法
     */
	public function wxLoginCallback(){
    	header ( "Content-type: text/html; charset=utf-8" );
    	vendor ( 'ThirdLogin.WxLogin' );

    	$appId = $GLOBALS['CONFIG']["wxAppId"];
    	$appKey = $GLOBALS['CONFIG']["wxAppKey"];

    	$wx = new \WxLogin($appId,$appKey);
    	//得到access_token验证值
    	$accessToken = $wx->getToken();
    	
    	if(!$accessToken){
    		$this->redirect("Home/Users/login");
    	}
    	//得到用户的openid(登陆用户的识别码)和Client_id
    	$openId = $wx->getOpenId();
    	if($openId!=""){
    		$um = D('Home/Users');
    		//已注册，则直接登录
    		if($um->checkThirdIsReg(2,$openId)){
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 2;
    			$rd = $um->thirdLogin($obj);
    			if($rd["status"]==1){
    				$this->redirect("Home/Index/index");
    			}else{
    				$this->redirect("Home/Users/login");
    			}
    		}else{
    			//未注册，则先注册
    			$arr = $wx->getUserInfo($openId,$accessToken);
    			$obj["userName"] = $arr["nickname"];
    			$obj["openId"] = $openId;
    			$obj["userFrom"] = 2;
    			$obj["userPhoto"] = $arr["headimgurl"];
    			$um->thirdRegist($obj);
    			$this->redirect("Home/Index/index");
    		}
    	}else{
    		$this->redirect("Home/Users/login");
    	}
    }
    
    
}