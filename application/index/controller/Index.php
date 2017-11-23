<?php

namespace app\index\controller;

use app\common\model\User;
use app\common\model\Userhis;
use app\common\model\Vote;
use think\Cache;
use think\Request;
use think\Session;

class Index extends IndexBaseController {
	public function __construct (Request $request = NULL)
	{
		parent::__construct ($request);
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
	}
	
	
	public function index ($fid = "")
	{
		$fid = intval ($fid) ? intval ($fid) : input ('get.fid/d');
		$fetch = "";
		if ( !$fid ) {
			return $this->fetch ('welcome');
		}
		$flist = $this->getFlist ($fid);
		if ( !$flist ) {
			return $this->fetch ('welcome');
		}
		if ( $this->getFlist ($fid)[ 'used' ] != 1 ) {//房间关闭或关停
			return $this->fetch ('stop');
		}
		if ( !$this->is_login_room ($fid) ) {//是否输入了房间密码
			return $this->redirect ("Index/room_login", ['fid' => $fid]);
		}
		if ( !Session::has ('user') ) {//没登录
			$this->make_user ($fid); //创建游客
		} else if ( $fid != Session::get ("user")[ 'fid' ] ) {//登录了但是没有访问跟session相对的房间，会被踢下线
			Session::clear ();
			
			return $this->redirect (url ("Index/index", ['fid' => $fid]));
		}
		
		if ( request ()->isMobile () ) {
			$fetch = "mobile";
		}

		$this->assign ('fid', $fid);
		
		
		return $this->fetch ($fetch);
	}
	
	/**
	 * 输入房间密码页面
	 *
	 * @param string $fid
	 *
	 * @return mixed
	 */
	public function room_login ($fid = "")
	{
		$fid = intval ($fid) ? intval ($fid) : input ('get.fid/d');
		if ( !$fid ) {
			return $this->fetch ('welcome');
		}
		if ( $this->is_login_room ($fid) ) {
			
			return $this->redirect ('Index/index', ['fid' => $fid]);
		}
		$fetch = "";
		if ( request ()->isMobile () ) {
			$fetch = "mroom_login";
		}
		$this->assign ('fid', $fid);
		
		return $this->fetch ($fetch);
	}
	
	/**
	 * 登陆
	 *
	 * @param string $fid
	 *
	 * @return mixed
	 */
	public function login ($fid = "")
	{
		$fid = intval ($fid) ? intval ($fid) : input ('get.fid/d');
		if ( !$fid ) {
			return $this->fetch ('welcome');
		}
		
		$fetch = "";
		if ( request ()->isMobile () ) {
			$fetch = "m_login";
		}
		$this->assign ('fid', $fid);
		
		return $this->fetch ($fetch);
	}
	
	/**
	 * 获取房间配置信息
	 *
	 * @param $fid
	 *
	 * @return array
	 */
	protected function pageConfig ($fid)
	{
		$flist = $this->getFlist ($fid);
		unset($flist[ 'fpassword' ], $flist[ 'createtime' ], $flist[ 'guolv' ], $flist[ 'id' ], $flist[ 'show_in_allchat' ], $flist[ 'show_in_online' ]);
		$flist[ 'kfqq' ] = $flist[ 'kfqq' ] ? explode ("|", $flist[ 'kfqq' ]) : "";
		$flist[ 'pl' ] = Session::get ("user")[ 'group' ] == 0 ? 0 : $flist[ 'pl' ];
		$config = [
			'gatewayHost' => config ('gatewayHost'),
			'fangjian' => $flist,
		];
		
		return $config;
	}
	
	/**
	 * 生成游客账号
	 *
	 * @param $fid
	 *
	 * @return bool
	 */
	protected function make_user ($fid)
	{
		Session::clear ();
		$group = $this->getGlist (3);
		$user = new User();
		
		$data = $user->make_youke ($group, $fid, $this->request->ip (0, TRUE));
		
		if ( $data ) {
			Session::set ('user', $data);
			$this->login_cache (); //记录缓存
		} else {
			return FALSE;
		}
	}
	
	/**
	 * 用户访问的页面配置接口
	 *
	 * @param string $fid
	 *
	 * @return string
	 */
	public function apiConfig ($fid = "")
	{
		config ('app_trace', FALSE);
		$fid = intval ($fid) ? intval ($fid) : input ('get.fid/d');
		$vote = new Vote();
		if ( !$fid ) {
			return "NO FID";
		}
		$str = "";
		
		$str .= "var pageConfig='" . json_encode ($this->pageConfig ($fid)) . "';";//页面配置
		if ( Session::has ('user') ) {
			$user = Session::get ('user');
			$str .= "var userInfo='" . json_encode ($user) . "';";//用户信息
		} else {
			$str .= "var userInfo='null';";//用户信息
		}
		$str .= "var voteList='" . json_encode ($vote->vote_with_item (1)) . "'";
		
		return $str;
	}
	
}