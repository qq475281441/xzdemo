<?php
/**
 * Created by PhpStorm.
 * User: zhouwenping
 * Date: 2017/11/17
 * Time: 上午10:02
 */

namespace app\index\controller;


use app\common\model\Userhis;
use think\Request;
use think\Session;
use think\Cache;
use app\common\controller\BaseController;

class IndexBaseController extends BaseController {
	public function __construct (Request $request = NULL)
	{
		parent::__construct ($request);
		$this->assign ('v', config ('VERSION') ? config ('VERSION') : 1);
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
	}
	
	/**
	 * 将登录数据缓存起来
	 * @internal param type $data
	 */
	public function login_cache ()
	{
		if ( !Session::has ('user') ) {
			return;
		}
		$ip = $this->request->ip (0, TRUE);
		$content = [
			'username' => Session::get ('user')[ 'username' ],
			'fid' => Session::get ('user')[ 'fid' ],
			'isshui' => Session::get ('user')[ 'isshui' ],
			'group' => Session::get ('user')[ 'group' ],
			'time' => time (),
			'ip' => $ip,
			'address' => get_area ($ip)
		];
		$userhis = new Userhis();
		
		$userhis->login ($content);
	}
	
	/**
	 * 判断是否登录了房间
	 *
	 * @param $fid
	 *
	 * @return bool
	 */
	public function is_login_room ($fid)
	{
		if ( trim ($this->getFlist ($fid)[ 'fpassword' ]) != "" && !Session::has ('fpassword') ) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
}