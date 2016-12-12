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
	 	if($cat){
	 	$map['catId']=$cat;
	 	}
	 	if($pcat){
	 		$map['parentId']=$pcat;
	 	}
	 	$map['isShow']=1;
	 	
	 	$offlist=D('offline')->where($map)->limit(20)->select();
	 	$this->assign('offlist',$offlist);
	 	
		 
		 $this->display('default/offline_index');
		 
	 }
	 //详情
	 public function details(){
	 	
	 	
	 	$catId=I('catId');//子类
	 		
	 	$this->display('default/offline_details');
	 		
	 }
	 
	 
	 
	 
	 
	 
	 
	   //我的圈子
	  public function myindex(){
		 
		 $this->display('default/my_index');
		 
	 }
	
	public function toEditPass(){
		$this->isLogin();
		$this->assign("umark","toEditPass");
		$this->display("default/users/edit_pass");
	}
	
	/**
	 * 修改用户密码
	 */
	public function editPass(){
		$this->isLogin();
		$USER = session('WST_USER');
		$m = D('Home/Users');
   		$rs = $m->editPass($USER['userId']);
    	$this->ajaxReturn($rs);
	}
	/**
	 * 跳去修改买家资料
	 */
	public function toEdit(){
		$this->isLogin();
		$m = D('Home/Users');
		$obj["userId"] = session('WST_USER.userId');
		$user = $m->getUserById($obj);
	
		//判断会员等级
		$USER = session('WST_USER');
		$rm = D('Home/UserRanks');
		$USER["userRank"] = $rm->getUserRank();
		session('WST_USER',$USER);
		
		$this->assign("user",$user);
		$this->assign("umark","toEditUser");
		$this->display("default/users/edit_user");
	}
	
	/**
	 * 跳去修改买家资料
	 */
	public function editUser(){
		$this->isLogin();
		$m = D('Home/Users');
		$obj["userId"] = session('WST_USER.userId');
		$data = $m->editUser($obj);
		
		$this->ajaxReturn($data);
	}
	
	/**
	 * 判断手机或邮箱是否存在
	 */
	public function checkLoginKey(){
		$m = D('Home/Users');
		$key = I('clientid');
		$userId = (int)session('WST_USER.userId');
		$rs = $m->checkLoginKey(I($key),$userId);
		if($rs['status']==1){
			$rs['msg'] = "该账号可用";
		}else if($rs['status']==-2){
			$rs['msg'] = "不能使用该账号";
		}else{
			$rs['msg'] = "该账号已存在";
		}
		$this->ajaxReturn($rs);
	}
	/**
	 * 忘记密码
	 */
    public function forgetPass(){
    	session('step',1);
    	$this->display('default/forget_pass');
    }
    
    /**
     * 找回密码
     */
    public function findPass(){
    	//禁止缓存
    	header('Cache-Control:no-cache,must-revalidate');  
		header('Pragma:no-cache');
    	$step = (int)I('step');
    	switch ($step) {
    		case 1:#第二步，验证身份
    			if (!$this->checkCodeVerify(false)) {
    				$this->error('验证码错误！');
    			}
    			$loginName = WSTAddslashes(I('loginName'));
    			$m = D('Home/Users');
    			$info = $m->checkAndGetLoginInfo($loginName);
    			if ($info != false) {
    				session('findPass',array('userId'=>$info['userId'],'loginName'=>$loginName,'userPhone'=>$info['userPhone'],'userEmail'=>$info['userEmail'],'loginSecret'=>$info['loginSecret']) );
    				if($info['userPhone']!='')$info['userPhone'] = WSTStrReplace($info['userPhone'],'*',3);
    				if($info['userEmail']!='')$info['userEmail'] = WSTStrReplace($info['userEmail'],'*',2,'@');
    				$this->assign('forgetInfo',$info);
    				$this->display('default/forget_pass2');
    			}else $this->error('该用户不存在！');
    			break;
    		case 2:#第三步,设置新密码
    			if (session('findPass.loginName') != null ){
                    if (session('findPass.userEmail')==null) {
                        $this->error('你没有预留邮箱，请通过手机号码找回密码！');
                    }
                    if ( session('findPass.userPhone') == null) {
    				    $this->error('你没有预留手机号码，请通过邮箱方式找回密码！');
                    }
    			}else $this->error('页面过期！');
    			break;
    		case 3:#设置成功
    			$resetPass = session('REST_success');
    			if($resetPass!='1')$this->error("非法的操作!");
                $loginPwd = I('loginPwd');
                $repassword = I('repassword');
                if ($loginPwd == $repassword) {
	                $rs = D('Home/Users')->resetPass();
			    	if($rs['status']==1){
			    	    $this->display('default/forget_pass4');
			    	}else{
			    		$this->error($rs['msg']);
			    	}
                }else $this->error('两次密码不同！');
    			break;
    		default:
    			$this->error('页面过期！'); 
    			break;
    	}  	
    }


	/**
	 * 手机验证码获取
	 */
	public function getPhoneVerify(){
		$rs = array('status'=>-1);
		if(session('findPass.userPhone')==''){
			$this->ajaxReturn($rs);
		}
		$phoneVerify = mt_rand(100000,999999);
		$USER = session('findPass');
		$USER['phoneVerify'] = $phoneVerify;
        session('findPass',$USER);
		$msg = "您正在重置登录密码，验证码为:".$phoneVerify."，请在30分钟内输入。【".$GLOBALS['CONFIG']['mallName']."】";
		$rv = D('Home/LogSms')->sendSMS(0,session('findPass.userPhone'),$msg,'getPhoneVerify',$phoneVerify);
		$rv['time']=30*60;
		$this->ajaxReturn($rv);
	}

	/**
	 * 手机验证码检测
	 * -1 错误，1正确
	 */
	public function checkPhoneVerify(){
		$phoneVerify = I('phoneVerify');
		$rs = array('status'=>-1);
		if (session('findPass.phoneVerify') == $phoneVerify ) {
			//获取用户信息
			$user = D('Home/Users')->checkAndGetLoginInfo(session('findPass.userPhone'));
			$rs['u'] = $user;
			if(!empty($user)){
				$rs['status'] = 1;
				$keyFactory = new \Think\Crypt();
			    $key = $keyFactory->encrypt("0_".$user['userId']."_".time(),C('SESSION_PREFIX'),30*60);
				$rs['url'] = "http://".$_SERVER['HTTP_HOST'].U('Home/Users/toResetPass',array('key'=>$key));
			}
		}
		$this->ajaxReturn($rs);
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