<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Loader;
use think\Cache;

// 应用公共文件
/**
 * 获取IP所在地，用淘宝接口
 *
 * @param type $ip
 *
 * @return type
 */
function get_area ($ip)
{
	Loader::import ('IpLocation', EXTEND_PATH);
	$location = new IpLocation();
	$data = $location->getlocation ($ip);
	$data = $data[ 'country' ] . $data[ 'area' ];
	
	return $data;
}

/**
 * 封装接口返回格式
 *
 * @param int    $code   0为正常
 * @param        $msg    返回信息
 * @param string $data   返回数据
 *
 * @return int
 */
function ResponseReturn ($code = 0, $msg, $data = '')
{
	$_return[ 'code' ] = $code;
	$_return[ 'msg' ] = $msg;
	$_return[ 'data' ] = $data;
	
	return json_encode ($_return);
}