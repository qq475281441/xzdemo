<?php
/**
 * Created by PhpStorm.
 * User: zhouwenping
 * Date: 2017/11/17
 * Time: 上午10:01
 */

namespace app\common\controller;


use GatewayWorker\Lib\Gateway;
use think\Cache;
use think\Controller;
use think\Request;

class BaseController extends Controller {
	protected $flist;//房间列表
	protected $glist;//用户组列表
	
	public function __construct (Request $request = NULL)
	{
		parent::__construct ($request);
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
		Gateway::$registerAddress = config ('registerAddress');
		$this->setFlist ();
		$this->setGlist ();
	}
	
	/**
	 * @return mixed
	 */
	public function getFlist ($fid)
	{
		$tmp = $this->flist;
		
		return isset($tmp[ $fid ]) ? $tmp[ $fid ] : FALSE;
	}
	
	/**
	 * 获取房间信息并保存
	 * @return array|mixed
	 * @internal param mixed $flist
	 *
	 */
	protected function setFlist ()
	{
		$tmp = [];
		if ( !Cache::has ('flist') || Cache::get ('flist') == "" ) {
			$flist = db ('fangjian')->select ();
			
			foreach ( $flist as $value ) {
				$tmp[ $value[ 'fid' ] ] = $value;
			}
			Cache::set ('flist', $tmp, 3600);
		} else {
			$tmp = Cache::get ('flist');
		}
		
		return $this->flist = $tmp;
	}
	
	/**
	 * @return mixed
	 */
	public function getGlist ($gid)
	{
		$tmp = $this->glist;
		
		return isset($tmp[ $gid ]) ? $tmp[ $gid ] : FALSE;
	}
	
	/**
	 * 获取分组列表并缓存
	 * @return array|mixed
	 * @internal param mixed $glist
	 */
	public function setGlist ()
	{
		$tmp = [];
		if ( !Cache::has ('glist') || Cache::get ('glist') == "" ) {
			$glist = db ('grouplist')->select ();
			
			foreach ( $glist as $value ) {
				$tmp[ $value[ 'groupid' ] ] = $value;
			}
			Cache::set ('glist', $tmp, 3600);
		} else {
			$tmp = Cache::get ('glist');
		}
		
		return $this->glist = $tmp;
	}
	
	
}