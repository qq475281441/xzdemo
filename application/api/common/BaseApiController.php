<?php
/**
 * Created by PhpStorm.
 * User: zhouwenping
 * Date: 2017/11/17
 * Time: 下午3:21
 */

class BaseApiController extends \think\Controller {
	public function __construct (\think\Request $request = NULL)
	{
		parent::__construct ($request);
	}
	
	protected function _initialize ()
	{
		parent::_initialize ();
	}
}