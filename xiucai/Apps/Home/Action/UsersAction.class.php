<?php
namespace Home\Action;
/**
 * ============================================================================
 * 
 * 联系QQ:1149100178
 * ============================================================================
 * 会员控制器
 */
class UsersAction extends BaseAction {
    /**
     * 跳去登录界面
     */
	public function login(){
		//如果已经登录了则直接跳转
		$USER = session('WST_USER');
		if(!empty($USER) && $USER['userId']!=''){
			$this->redirect("Users/index");
		}
		if(isset($_COOKIE["loginName"])){
			$this->assign('loginName',$_COOKIE["loginName"]);
		}else{
			$this->assign('loginName','');
		}
		$this->assign('qqBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/qqlogin.php"));
		$this->assign('wxBackUrl',urlencode(WSTDomain()."/Wstapi/thridLogin/wxlogin.php"));
		$this->display('default/login');
	}
	
	
	/**
	 * 用户退出
	 */
	public function logout(){
		session('WST_USER',null);
		setcookie("loginPwd", null);
		echo "1";
	}
	
	/**
     * 注册界面
     * 
     */
	public function regist(){
		if(isset($_COOKIE["loginName"])){
			$this->assign('loginName',$_COOKIE["loginName"]);
		}else{
			$this->assign('loginName','');
		}
		$this->display('default/regist');
	}

	/**
	 * 验证登陆
	 * 
	 */
	public function checkLogin(){
	    $rs = array();
	    $rs["status"]= 1;
		/* if(!$this->checkVerify("4") && ($GLOBALS['CONFIG']["captcha_model"]["valueRange"]!="" && strpos($GLOBALS['CONFIG']["captcha_model"]["valueRange"],"3")>=0)){			
			$rs["status"]= -1;//验证码错误
		}else{ */
			$m = D('Home/Users');			
			$res = $m->checkLogin();
			if (!empty($res)){
				if($res['userFlag'] == 1){
					session('WST_USER',$res);
					unset($_SESSION['toref']);
					if(strripos($_SESSION['refer'],"regist")>0 || strripos($_SESSION['refer'],"logout")>0 || strripos($_SESSION['refer'],"login")>0){
						$rs["refer"]= __ROOT__;
					}						
				}else if($res['status'] == -1){
					$rs["status"]= -2;//登陆失败，账号或密码错误
				}
			} else {
				$rs["status"]= -2;//登陆失败，账号或密码错误
			}
			
			$rs["refer"]= $rs['refer']?$rs['refer']:__ROOT__;
		
		echo json_encode($rs);
	}

	/**
	 * 新用户注册
	 */
	public function toRegist(){
		$m = D('Home/Users');
		$res = array();
		$verify = new \Think\Verify();
	    $ve=$verify->check(I('verify'));
	    //$ve = true;
		if( $ve == false){
		      $res['status']='-4';
			  $res["msg"] = '验证码不正确!';
			  echo json_encode($res);
			  return false;
		}

		  $smscode=I('smscode');
		  $times=time();//当前时间
		 $time2=session('VerifyCode_userPhone_Time');
		if( ($times-$time2)>60){
			$res['status']='-5';
			$res["msg"] = '短信验证码超时!';
			echo json_encode($res);
			return false;
			
		}
		
		$VerifyCode_userPhone=session('VerifyCode_userPhone');
		if($smscode!=$VerifyCode_userPhone){
			
			$res['status']='-6';
			$res["msg"] = '短信验证码输入错误!';
			echo json_encode($res);
			return false;
			
		}
		
			$res = $m->regist();
			if($res['userId']>0){//注册成功			
				//加载用户信息				
				$user = $m->get($res['userId']);
				if(!empty($user))session('WST_USER',$user);
				
			}
		
		echo json_encode($res);

	}
    
 	/**
	 * 获取验证码
	 */
	public function getPhoneVerifyCode(){
		vendor ('Sms.CCPRestSDK' );
		$userPhone = WSTAddslashes(I("userPhone"));
		$rs = array();
		//
		if(!preg_match('/^[1]+[3,4,5,7,8]+\d{9}$/', $userPhone)){
			$rs["msg"] = '手机号格式不正确!';
			echo $rs["msg"];
			exit();
		}
		$verify = new \Think\Verify();
        $ve = $verify->check(I('yzm'));
        if ($ve == false){
            echo json_encode(2);exit;
        }
		$m = D('Home/Users');
		$rs = $m->checkUserPhone($userPhone,(int)session('WST_USER.userId'));
		if($rs["status"]!=1){
			$rs["msg"] = '手机号已存在!';
			echo $rs["msg"];
			exit();
		}
		$phoneVerify = rand(100000,999999);
		//$msg = "欢迎您注册成为".$GLOBALS['CONFIG']['mallName']."会员，您的注册验证码为:".$phoneVerify."，请在30分钟内输入。【".$GLOBALS['CONFIG']['mallName']."】";
		//$rv = D('Home/LogSms')->sendSMS(0,$userPhone,$msg,'getPhoneVerifyByRegister',$phoneVerify);
		
		
		//主帐号
		$accountSid='aaf98f894c9d994b014ca1fd595e0358';
		//主帐号Token
		$accountToken='9964514651ed42ad8d37a50c5e711f52';
		//应用Id
		$appId='8a216da859204cc901592ac13ee006f3';
		//请求地址，格式如下，不需要写https://
		$serverIP='app.cloopen.com';
		//请求端口
		$serverPort='8883';
		//REST版本号
		$softVersion='2013-12-26';
		$to=$userPhone;
		$tempId="149107";
		$datas=array($phoneVerify,'60s');
		
		$rest = new \REST($serverIP,$serverPort,$softVersion);
		$rest->setAccount($accountSid,$accountToken);
		$rest->setAppId($appId);
		
		// 发送模板短信
		//echo "Sending TemplateSMS to $to <br/>";
		$result = $rest->sendTemplateSMS($to,$datas,$tempId);
		if($result == NULL ) {
			echo "result error!";
			exit;
		}
		if($result->statusCode!=0) {
			echo "error code :" . $result->statusCode . "<br>";
			echo "error msg :" . $result->statusMsg . "<br>";
			//TODO 添加错误处理逻辑
		}else{
			//echo "Sendind TemplateSMS success!<br/>";
			// 获取返回信息
			$smsmessage = $result->TemplateSMS;
		//	echo "dateCreated:".$smsmessage->dateCreated."<br/>";
			//echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
			session('VerifyCode_userPhone',$phoneVerify);
			session('VerifyCode_userPhone_Time',time());
			$arr=1;
			echo json_encode($arr);
			//TODO 添加成功处理逻辑
		}
		
		
		
	}
   /**
    * 会员中心页面
    */
	public function index(){
		$this->isUserLogin();
		$USER = session('WST_USER');
		$id = $USER['userId'];
        $USER = D('users')->where("userId = $id")->find();
		//判断会员等级
		$rm = D('Home/UserRanks');
		$USER["userRank"] = $rm->getUserRank();
        $USER["userFollow"] = D('follow')->where("userId = $id")->count();
        $USER['industry'] = D('shop_industry')->where("id = {$USER['industry']}")->find();
        $USER['industry'] = $USER['industry']['name'];
		$this->assign('WST_USER',$USER);
//        dump($USER);exit;
		
		//获取订单列表
		$morders = D('Home/Orders');
		$obj["userId"] = (int)$USER['userId'];
		$orderList = $morders->queryByPage($obj);
		//dump($orderList);
		//exit;
		$statusList = $morders->getUserOrderStatusCount($obj);
		$um = D('Home/Users');
		$user = $um->getUserById(array("userId"=>session('WST_USER.userId')));
	//	$this->assign("userScore",$user['userScore']);
		$this->assign("orderList",$orderList);
		$this->assign("statusList",$statusList);
		//我的问答
        $myquestion = D('questions')->field('id,title')->where("userId = {$USER['userId']}")->select();
		foreach ($myquestion as $k=>$v) {
            $myquestion[$k]['answers'] = D('answers')->where("qId = {$v['id']}")->select();
        }
        $this->assign('myquestion',$myquestion);
//        dump($myquestion);die;
        //老师列表
		 $specialist=D('Shops')->where(array('shopStatus'=>1,'shopFlag'=>1))->limit(3)->select();
		foreach($specialist  as  $k=>$v){
			$specialist[$k]['shopGoodat']=explode(',' , $v['shopGoodat']);
		}
		//var_dump($specialist);
		// exit;
		 $this->assign('specialist',$specialist);
		//我的圈子动态
		//1我加入的圈子
        $forum = D('forum_record')->field('cid,catName')->join("__FORUM_CATS__  on cid = catId")->where("uid = {$USER['userId']}")->select();
        $this->assign('forum',$forum);
//        dump($forum);die;
        //2我的发帖
        $where = "wst_forum.isShow = 1 and staffId = {$USER['userId']}";
        $order = 'articleId desc';
        $article = D('forum')->field('articleId,wst_forum.catId,wst_forum.parentCatId,c.catName,articleTitle,staffId,createTime,readNum,commentNum,lastId,lastTime')->join("wst_forum_cats c on wst_forum.parentCatId = c.catId")->where($where)->order($order)->select();
//        dump($article);die;
        $this->assign('article' , $article);
        //3我的回复
        $mycomment = D('forum_comment')->field('id,uid,cuid,aid,catId,parentCatId,articleTitle,content,parentId,ctime')->join("__FORUM__ on aid = articleId")->where("uid = {$USER['userId']}")->order('ctime desc')->select();
        foreach ($mycomment as $k=>$v) {
            if ($v['parentId'] != 0) {
                $mycomment[$k]['cuname'] = D('users')->field('loginName')->where("userId = {$v['cuid']}")->find();
                $mycomment[$k]['cuname'] = $mycomment[$k]['cuname']['loginName'];
            }
        }
        $this->assign('mycomment',$mycomment);
//        dump($mycomment);die;
        //4回复我的
        $comment = D('forum_comment')->field('id,uid,cuid,aid,catId,parentCatId,articleTitle,content,parentId,ctime')->join("__FORUM__ on aid = articleId")->where("cuid = {$USER['userId']}")->order('ctime desc')->select();
        foreach ($comment as $k => $v) {
            $comment[$k]['loginName'] = D('users')->field('loginName')->where("userId = {$v['uid']}")->find();
            $comment[$k]['loginName'] = $comment[$k]['loginName']['loginName'];
            $comment[$k]['userPhoto'] = D('users')->field('userPhoto')->where("userId = {$v['uid']}")->find();
            $comment[$k]['userPhoto'] = $comment[$k]['userPhoto']['userPhoto'];
        }
        $this->assign('comment' , $comment);
//        dump($comment);die;
		//我的课程
	
		$userId=$obj["userId"];
		//SELECT o.orderId,o.orderNo ,c.courseId ,oc.courseId FROM  wst_orders o LEFT JOIN   wst_order_course  oc  ON  o.orderId=oc.orderId  left join   wst_course c   ON  c.courseId=oc.courseId   where o.orderStatus=2 AND o.userId=42;
		$sql=" SELECT o.orderId,o.orderNo,c.courseId ,c.videoPath,c.courseName,c.courseTime,c.courseDifficulty,c.is_free,c.saleCount,c.courseThums FROM  wst_orders o LEFT JOIN   wst_order_course  oc  ON  o.orderId=oc.orderId  left join   wst_course c   ON  c.courseId=oc.courseId   where o.orderStatus=2 AND o.userId=$userId";

		$result=D('Orders')->query($sql);
		//foreach ($result  as  $k=>$v){
	
		//}
//		dump($result);exit;
	//	setVpath($userId,$result['videoPath']);
	
		$this->assign('userresult',$result);
		
		//var_dump($result);exit;

        //近期直播
        $arr = D('Live')->related();
        $this->assign('relatedArr' , $arr);

		$this->display("default/users/index");
	}
	
   /**
    * 跳到修改用户密码
    */
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
		$oldPhoto = $_POST['userPhoto'];
//		echo __ROOT__ . "/" . $oldPhoto;die;
        $_POST['userPhoto'] = $this->uploadUserPic();
		$re = D('users')->where("userId = {$_POST['userId']}")->save($_POST);
		if ($re) {
		    unlink(__ROOT__ . "/" . $oldPhoto);
		    successS('保存成功' , U('Home/Users/index'));
        } else {
		    alert('保存失败');
        }
	}

    /**
     * @return bool
     * 头像上传
     */
    public function uploadUserPic() {
        $config = array(
            'maxSize'  => 0 , //上传的文件大小限制 (0-不做限制)
            'exts'     => array('jpg' , 'png' , 'gif' , 'jpeg') , //允许上传的文件后缀
            'rootPath' => './Upload/' , //保存根路径
            'driver'   => 'LOCAL' , // 文件上传驱动
            'subName'  => array('date' , 'Y-m') ,
            'savePath' => I('dir' , 'uploads') . "/"
        );
        $dirs = explode("," , C("WST_UPLOAD_DIR"));
        if (!in_array(I('dir' , 'uploads') , $dirs)) {
            echo '非法文件目录！';
            return false;
        }

        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        //dump($rs);
        //exit;
        $Filedata = key($_FILES);
        if (!$rs) {
            $this->error($upload->getError());
        } else {
            $rs[$Filedata]['savepath'] = "Upload/" . $rs[$Filedata]['savepath'] . $rs[$Filedata]['savename'];
            return $rs[$Filedata]['savepath'];
        }
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
	
	
	//开通会员说明
	public function open_member(){
		$article = D('articles')->where("catId = 7")->find();
	    $this->assign('article',$article);
		$this->display("default/users/openintro");
	}
	
	
	
    //视屏播放页面
	public  function  videoPlay(){
		$this->isUserLogin();
		
		$orderId=I('orderId');
		
		$orderOne=D('orders')->where(array('orderNo'=>$orderId))->find();
		if(!$orderOne){
			$this->error('当前视屏不存在');
		}
		$orderCourse=D('order_course')->where(array('orderId'=>$orderOne['orderId']))->find();
		$courseOne=D('course')->where(array('courseId'=>$orderCourse['courseId']))->find();
		if(!$courseOne){
			$this->error('当前课程不存在');
		}

		$this->assign('courseOne',$courseOne);

		//dump($courseOne);
	
		$this->display("default/users/videoplay");
		 
	}

    public function toOpenShop() {
        $this->isUserLogin();
        //判断用户是否已提交审核资料
        $userId = session('WST_USER.userId');
        $result = D('shops')->where("userId = {$userId}")->find();
//        dump($result['shopStatus']);die;
        if ($result['shopStatus'] === '0' || $result['shopStatus'] == 1) {
            $this->assign('auth',1);  //已提交
        } elseif ($result['shopStatus'] == -1) {
            $this->assign('auth' , 2);  //未提交
            $this->assign('result' , $result);//用户信息
        }else {
            $this->assign('auth' , 2);  //未提交
        }
        $this->display("default/users/openShop");
    }

    public function toAuth() {
        $this->isUserLogin();
        if ($_POST){
            if ($_POST['save']){//需要重新审核的
                unset($_POST['save']);
                $_POST['userId'] = session('WST_USER.userId');
                $_POST['shopCompany'] = $_POST['shopName'];
                $_POST['createTime'] = date('Y-m-d H:i:s' , time());
                $_POST['shopStatus'] = 0;
                if ($_FILES['shopImg']['name']) {
                    $imgarr = $this->uploadShopPic();
                    if ($imgarr[0]) {
                        $_POST['shopImg'] = "Upload/" . $imgarr[0]['savepath'] . $imgarr[0]['savename'];
                    }
                    if ($imgarr[1]) {
                        $_POST['shopInfoImg'] = "Upload/" . $imgarr[1]['savepath'] . $imgarr[1]['savename'];
                    }
                    if ($imgarr[2]) {
                        $_POST['shopCertImg'] = "Upload/" . $imgarr[2]['savepath'] . $imgarr[2]['savename'];
                    }
                }
                $result = D('shops')->where("userId = {$_POST['userId']}")->save($_POST);
                if ($result) {
                    $this->success('提交成功,待审核',U('Home/Users/toOpenShop'));
                } else {
                    $this->error('未知错误');
                }
            } else { //新提交的
//                dump($_FILES);die;
                $_POST['userId'] = session('WST_USER.userId');
                $_POST['shopCompany'] = $_POST['shopName'];
                $_POST['createTime'] = date('Y-m-d H:i:s' , time());
                if ($_FILES['shopImg']['name']) {
                    $imgarr = $this->uploadShopPic();
                    if ($imgarr[0]){
                        $_POST['shopImg'] = "Upload/".$imgarr[0]['savepath']. $imgarr[0]['savename'];
                    }
                    if ($imgarr[1]) {
                        $_POST['shopInfoImg'] = "Upload/".$imgarr[1]['savepath'] . $imgarr[1]['savename'];
                    }
                    if ($imgarr[2]) {
                        $_POST['shopCertImg'] = "Upload/".$imgarr[2]['savepath'] . $imgarr[2]['savename'];
                    }
                }
                $result = D('shops')->add($_POST);
                if ($result) {
                    $this->success('提交成功,待审核',U('Home/Users/toOpenShop'));
                } else {
                    $this->error('未知错误');
                }
            }
        } else {
            $this->redirect("Home/Index/index");
        }
    }



    public function uploadShopPic() {
        $config = array(
            'maxSize'  => 0 , //上传的文件大小限制 (0-不做限制)
            'exts'     => array('jpg' , 'png' , 'gif' , 'jpeg') , //允许上传的文件后缀
            'rootPath' => './Upload/' , //保存根路径
            'driver'   => 'LOCAL' , // 文件上传驱动
            'subName'  => array('date' , 'Y-m') ,
            'savePath' => I('dir' , 'shops') . "/"
        );
        $dirs = explode("," , C("WST_UPLOAD_DIR"));
        if (!in_array(I('dir' , 'shops') , $dirs)) {
            echo '非法文件目录！';
            return false;
        }
        $upload = new \Think\Upload($config);
        $rs = $upload->upload($_FILES);
        $Filedata = key($_FILES);
        if (!$rs) {
            $this->error($upload->getError());
        } else {
            $rs[$Filedata]['savepath'] = "Upload/" . $rs[$Filedata]['savepath'] . $rs[$Filedata]['savename'];
            return $rs;
        }
    }
}