<?php
/**
 * Created by PhpStorm.
 * User: zhouwenping
 * Date: 2017/11/17
 * Time: 下午3:22
 */

namespace app\api\controller;

use app\common\model\User;
use think\Session;
use app\common\controller\BaseController;

class Login extends BaseController {
	public function __construct (\think\Request $request = NULL)
	{
		parent::__construct ($request);
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
	}
	
	/**
	 * 直播间登陆接口
	 */
	public function dologin ()
	{
		$fid = input ('.fid/d');
		$username = input ('post.username');
		$password = input ('post.password');
		$captcha = input ('post.captcha');
		if ( !captcha_check ($captcha) ) {
			
			return ResponseReturn (1, "验证码错误");
		}
		$user = new User();
		$r = $user->check_login ($username, $password, $fid);
		if ( $r != FALSE ) {
			unset($r[ 'password' ]);
			$r[ 'islogin' ] = 1;
			$group = $this->getGlist ($r[ 'group' ]);
			$r[ 'groupname' ] = $group[ 'groupname' ];
			$r[ 'image' ] = str_replace ("\\", "/", $group[ 'image' ]);
			
			Session::set ('user', $r);
			
			return ResponseReturn (0, '登陆成功');
		} else {
			return ResponseReturn (1, $user->getError ());
		}
		
		
	}
	
	/**
	 * 登陆房间密码接口
	 * @return int
	 */
	public function room_dologin ()
	{
		$fid = input ('.fid/d');
		$fpassword = input ('post.password');
		if ( $fid == "" || $fpassword == "" ) {
			return ResponseReturn (1, '参数不全');
		}
		$pass = $this->getFlist ($fid)[ 'fpassword' ];
		if ( $pass == $fpassword ) {
			Session::set ('fpassword', $fid);
			
			return ResponseReturn (0, '房间密码正确');
		}
	}
}