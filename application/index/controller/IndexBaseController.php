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
	
	
}