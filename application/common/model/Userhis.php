<?php

namespace app\common\model;

use think\Cache;
use think\Model;

class Userhis extends Model {
	protected $name = "userhis";
	
	protected function initialize ()
	{
		parent::initialize ();
	}
	
	/**
	 * 注册查询前事件
	 */
	protected static function init ()
	{
	}
	
	/**
	 * 用户登录历史缓存控制
	 *
	 * @param $content
	 */
	public function login ($content)
	{
		if ( !Cache::has ('login_cache') ) {
			#如果之前没有缓存
			$data[ 0 ] = $content;
			Cache::set ('login_cache', $data); //写缓存
		} else {
			$tmp = Cache::get ('login_cache');
			$i = count ($tmp);
			if ( $i >= 300 ) {
				$r = $this->save ($tmp);
				if ( $r ) {
					Cache::rm ('login_cache');
					$data[] = $content;
					Cache::set ('login_cache', $data); //写缓存
				}
			} else {
				array_push ($tmp, $content);
				Cache::set ('login_cache', $tmp); //写缓存
			}
		}
	}
}
