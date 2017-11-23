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
	
	public function __construct (Request $request = NULL)
	{
		parent::__construct ($request);
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
		Gateway::$registerAddress = config ('registerAddress');
	}
	
	
}